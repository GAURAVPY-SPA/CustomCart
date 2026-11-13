<?php

namespace App\Libraries;

use Closure;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Validation\ValidationInterface;

/**
 * The CodeIgniter 3 form validation API (set_rules / set_message / run /
 * error_array) on top of CodeIgniter 4's Validation service, so the
 * migrated controllers keep their rule sets and messages unchanged.
 *
 * It reproduces the CodeIgniter 3 behaviour the sitepanel depends on:
 *  - 'trim' changes the submitted value (in the request), before the other
 *    rules, so validation, set_value() and later getPost() see it trimmed;
 *  - a field that is empty and not 'required' skips every other rule
 *    (CodeIgniter 4 needs 'permit_empty' for that), except callables and
 *    callbacks, which still run;
 *  - rules run in CodeIgniter 3's order (_prepare_rules()): callables and
 *    callbacks first ('trim' counts as one, so a callable listed after it
 *    gets the trimmed value), then 'required', then the others as listed;
 *    the first failing rule stops the field;
 *  - rule strings are split on "|" outside [...] (regex_match patterns);
 *  - set_message() sets a message for a rule for the whole run;
 *  - named callables array('name', callable) and 'callback_<method>' rules,
 *    with their message looked up by that name;
 *  - error_array(): field => first error, in rule order.
 *
 * Messages use {field} (the label) and {param}, as in CodeIgniter 3; rule
 * messages not set here are CodeIgniter 4's, which are worded as in
 * CodeIgniter 3 (see app/Language/en/Validation.php).
 */
class FormValidation
{
    /**
     * field => ['label' => string, 'rules' => list<array|string>, 'errors' => array]
     */
    protected array $fields = [];

    /**
     * rule => message for the whole run (CodeIgniter 3 set_message()).
     */
    protected array $messages = [];

    protected ?array $data = null;

    /**
     * The data as submitted, before 'trim' (what callables receive).
     */
    protected array $raw = [];

    protected ValidationInterface $validation;

    /**
     * @param IncomingRequest $request    The request whose POST data is validated
     * @param object|null     $controller Object holding 'callback_<method>' methods
     */
    public function __construct(protected IncomingRequest $request, protected ?object $controller = null)
    {
        // The shared service, so form_error() in the views sees the result.
        $this->validation = service('validation');
        $this->validation->reset();
    }

    /**
     * @param array|string $rules CodeIgniter 3 rules: "a|b[x]" or a list (strings and [name, callable])
     * @param array        $errors rule (or callable name) => message for this field
     */
    public function set_rules(string $field, string $label, $rules, array $errors = []): self
    {
        $this->fields[$field] = [
            'label'  => $label,
            'rules'  => is_string($rules) ? preg_split('/\|(?![^\[]*\])/', $rules) : $rules,
            'errors' => array_map([$this, 'placeholders'], $errors),
        ];

        return $this;
    }

    /**
     * @param array|string $rule rule => message, or one rule
     */
    public function set_message($rule, ?string $message = null): self
    {
        $messages       = is_array($rule) ? $rule : [$rule => (string) $message];
        $this->messages = array_merge($this->messages, array_map([$this, 'placeholders'], $messages));

        return $this;
    }

    /**
     * CodeIgniter 3 also accepted sprintf-style messages: the first %s is the
     * field label, the second the rule parameter.
     */
    protected function placeholders(string $message): string
    {
        foreach (['{field}', '{param}'] as $placeholder) {
            if (($pos = strpos($message, '%s')) !== false) {
                $message = substr_replace($message, $placeholder, $pos, 2);
            }
        }

        return $message;
    }

    /**
     * Validate this array instead of the POST data (CodeIgniter 3 set_data()).
     */
    public function set_data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function run(): bool
    {
        $data      = $this->data ?? (array) $this->request->getPost();
        $this->raw = $data;

        // 1. 'trim' prepping, as CodeIgniter 3 did, in the data that is validated (and in the request).
        $trim = [];

        foreach ($this->fields as $field => $spec) {
            if (in_array('trim', $spec['rules'], true) && isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                $trim[]       = $field;
            }
        }
        if ($trim && $this->data === null) {
            $post = (array) $this->request->getPost();

            foreach ($trim as $field) {
                $post[$field] = $data[$field];
            }
            $this->request->setGlobal('post', $post);
        } elseif ($this->data !== null) {
            $this->data = $data;
        }

        // 2. CodeIgniter 4 rule sets, in CodeIgniter 3's order (_prepare_rules()):
        //    callables and callbacks first, then 'required', then the other rules.
        $rules = [];

        foreach ($this->fields as $field => $spec) {
            $callables = [];
            $list      = [];
            $messages  = [];
            $required  = false;
            // 'trim' is itself a callable in CodeIgniter 3, run in its place among
            // the callables: those listed after it get the trimmed value.
            $trimmed = false;

            foreach ($spec['rules'] as $rule) {
                if ($rule === 'trim') {
                    $trimmed = true;

                    continue;
                }
                if ($rule === '') {
                    continue;
                }
                if (is_array($rule) && count($rule) === 2 && is_string($rule[0])) {
                    // [name, callable]: a closure that sets the named message.
                    $callables[] = $this->closure($rule[0], $rule[1], $spec, $trimmed);

                    continue;
                }
                if ($rule instanceof Closure) {
                    $callables[] = $rule;

                    continue;
                }
                if (is_string($rule) && str_starts_with($rule, 'callback_')) {
                    $method      = substr($rule, 9);
                    $callables[] = $this->closure($method, [$this->controller, $method], $spec, $trimmed);

                    continue;
                }

                $name = preg_replace('/\[.*$/s', '', $rule);
                if ($name === 'required') {
                    $required = true;
                    array_unshift($list, $rule);
                } else {
                    $list[] = $rule;
                }
                if (isset($spec['errors'][$name])) {
                    $messages[$name] = $spec['errors'][$name];
                } elseif (isset($this->messages[$name])) {
                    $messages[$name] = $this->messages[$name];
                }
            }

            // CodeIgniter 3: an empty field that is not required skips the
            // other rules; callables still run.
            if (! $required) {
                $value = $data[$field] ?? null;
                if ($value === null || $value === '') {
                    $list = [];
                }
            }

            $list          = array_merge($callables, $list);
            $rules[$field] = ['label' => $spec['label'], 'rules' => $list ?: ['permit_empty'], 'errors' => $messages];
        }

        return $this->validation->setRules($rules)->run($data);
    }

    /**
     * field => first error message, in rule order.
     */
    public function error_array(): array
    {
        return $this->validation->getErrors();
    }

    public function error(string $field): string
    {
        return $this->validation->getError($field);
    }

    /**
     * A CodeIgniter 4 closure rule for a named CodeIgniter 3 callable; on
     * failure it sets the message registered under that name.
     *
     * @param bool $after_trim Whether 'trim' was listed before this callable
     *                         (it then gets the trimmed value, otherwise the value as submitted)
     */
    protected function closure(string $name, callable $callable, array $spec, bool $after_trim = false): Closure
    {
        $message = $spec['errors'][$name] ?? $this->messages[$name]
            ?? 'Unable to access an error message corresponding to your field name {field}.(' . $name . ')';
        $label = $spec['label'];
        $raw   = &$this->raw;

        return static function ($value, $data = null, &$error = null, $field = null) use ($callable, $message, $label, &$raw, $after_trim): bool {
            if (! $after_trim && $field !== null && array_key_exists($field, $raw)) {
                $value = $raw[$field];
            }
            $passed = (bool) $callable($value);
            if (! $passed) {
                $error = str_replace('{field}', $label, $message);
            }

            return $passed;
        };
    }
}
