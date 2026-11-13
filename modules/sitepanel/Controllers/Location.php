<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\CityModel;
use Modules\Sitepanel\Models\CountryModel;
use Modules\Sitepanel\Models\StateModel;

/**
 * Sitepanel > Other Management > Manage Location: Country -> State -> City
 * (wl_countries -> wl_states -> wl_cities).
 *
 * Each level has a list with name search and status filter, add / edit,
 * activate / deactivate and delete (one row or the selected rows). A
 * country's list links to its states, a state's list to its cities.
 * Deleting a country deletes its states and their cities; deleting a state
 * deletes its cities. A state can be moved to another country and a city
 * to another state of the same country. Names are unique within their
 * parent. One sub-admin grant ("location") covers all three levels.
 *
 * The website's delivery address form (checkout, My Address) offers the
 * active states and cities of the delivery country:
 * Modules\Cart\Libraries\DeliveryAddress.
 *
 *   index                     location/view_country_list
 *   add, edit/<id>            location/view_country_form
 *   status/<id>, delete/<id>  POST; bulk POST
 *   states/<country_id>       location/view_state_list
 *   state_add/<country_id>, state_edit/<id>   location/view_state_form
 *   state_status/<id>, state_delete/<id>      POST; state_bulk/<country_id> POST
 *   cities/<state_id>         location/view_city_list
 *   city_add/<state_id>, city_edit/<id>       location/view_city_form
 *   city_status/<id>, city_delete/<id>        POST; city_bulk/<state_id> POST
 */
class Location extends AdminController
{
    /**
     * Letters (any language), spaces and . , ' ( ) - & / — country, state and city names.
     */
    public const NAME_PATTERN = "/^[\\p{L}\\p{M} .,'()&\\/-]+$/u";

    protected string $base           = 'sitepanel/location';
    protected string $item           = 'Country';
    protected string $menu_highlight = 'location';

    protected CountryModel $country_model;
    protected StateModel $state_model;
    protected CityModel $city_model;

    protected function init(): void
    {
        $this->country_model = new CountryModel();
        $this->state_model   = new StateModel();
        $this->city_model    = new CityModel();
    }

    /* ---------------------------------------------------------------
     * Countries
     * --------------------------------------------------------------- */

    public function index(): string
    {
        [$keyword, $status] = $this->filters('get');
        $page = $this->current_page();
        $res  = $this->country_model->get_countries(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Location';
        $data['breadcrumbs']   = ['Location' => null];
        $data['res']           = $res;
        $data['total']         = $this->country_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('location/view_country_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_country(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Country added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Country';
        $data['breadcrumbs']   = ['Location' => site_url($this->base), 'Add Country' => null];
        $data['res']           = ['name' => '', 'iso_code_2' => '', 'iso_code_3' => ''];

        return $this->render('location/view_country_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->country_model->get_country_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_country($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Country updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Country';
        $data['breadcrumbs']   = ['Location' => site_url($this->base), 'Edit Country' => null];
        $data['res']           = $res;

        return $this->render('location/view_country_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(CountryModel::TABLE, CountryModel::PRIMARY_KEY, $id);
    }

    /**
     * Deletes the country, its states and their cities.
     *
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->delete_with_children($this->country_model, $this->record_or_back($this->country_model->get_country_by_id($id)), CountryModel::PRIMARY_KEY);
    }

    public function bulk(): void
    {
        [$keyword, $status] = $this->filters('post');
        $this->process_bulk_action($this->country_model, 'get_countries_by_ids', 'Country', array_filter(['keyword' => $keyword, 'status' => $status]));
    }

    /* ---------------------------------------------------------------
     * States of a country
     * --------------------------------------------------------------- */

    /**
     * @param int|string $country_id
     */
    public function states($country_id = 0): string
    {
        $country = $this->record_or_back($this->country_model->get_country_by_id($country_id));
        $this->for_states($country);

        [$keyword, $status] = $this->filters('get');
        $page = $this->current_page();
        $res  = $this->state_model->get_states((int) $country['country_id'], ($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage State — ' . $country['name'];
        $data['breadcrumbs']   = ['Location' => site_url('sitepanel/location'), $country['name'] => null];
        $data['country']       = $country;
        $data['res']           = $res;
        $data['total']         = $this->state_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('location/view_state_list', $data);
    }

    /**
     * @param int|string $country_id
     */
    public function state_add($country_id = 0): string
    {
        $country = $this->record_or_back($this->country_model->get_country_by_id($country_id));
        $this->for_states($country);
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_state(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'State added.');
                redirect_now('sitepanel/location/states/' . (int) $this->request->getPost('country_id'));
            }
        }

        $data['heading_title'] = 'Add State';
        $data['breadcrumbs']   = ['Location' => site_url('sitepanel/location'), $country['name'] => site_url($this->base), 'Add State' => null];
        $data['country']       = $country;
        $data['countries']     = $this->country_model->get_country_options();
        $data['res']           = ['country_id' => $country['country_id'], 'state_name' => ''];

        return $this->render('location/view_state_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function state_edit($id = 0): string
    {
        [$res, $country] = $this->state_and_country($id);
        $this->for_states($country);
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_state($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'State updated.');
                redirect_now('sitepanel/location/states/' . (int) $this->request->getPost('country_id'));
            }
        }

        $data['heading_title'] = 'Edit State';
        $data['breadcrumbs']   = ['Location' => site_url('sitepanel/location'), $country['name'] => site_url($this->base), 'Edit State' => null];
        $data['country']       = $country;
        $data['countries']     = $this->country_model->get_country_options();
        $data['res']           = $res;

        return $this->render('location/view_state_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function state_status($id = 0): void
    {
        $this->require_post();
        $this->for_states(['country_id' => $this->state_or_back($id)['country_id']]);
        $this->update_status(StateModel::TABLE, StateModel::PRIMARY_KEY, $id);
    }

    /**
     * Deletes the state and its cities.
     *
     * @param int|string $id
     */
    public function state_delete($id = 0): void
    {
        $this->require_post();
        $state = $this->state_or_back($id);
        $this->for_states($state);
        $this->delete_with_children($this->state_model, $state, StateModel::PRIMARY_KEY);
    }

    /**
     * @param int|string $country_id
     */
    public function state_bulk($country_id = 0): void
    {
        $this->require_post();
        $country = $this->record_or_back($this->country_model->get_country_by_id($country_id));
        $this->for_states($country);
        [$keyword, $status] = $this->filters('post');
        $this->process_bulk_action($this->state_model, 'get_states_by_ids', 'State', array_filter(['keyword' => $keyword, 'status' => $status]));
    }

    /* ---------------------------------------------------------------
     * Cities of a state
     * --------------------------------------------------------------- */

    /**
     * @param int|string $state_id
     */
    public function cities($state_id = 0): string
    {
        [$state, $country] = $this->state_and_country($state_id);
        $this->for_cities($state);

        [$keyword, $status] = $this->filters('get');
        $page = $this->current_page();
        $res  = $this->city_model->get_cities((int) $state['state_id'], ($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage City — ' . $state['state_name'];
        $data['breadcrumbs']   = $this->city_breadcrumbs($country, $state);
        $data['country']       = $country;
        $data['state']         = $state;
        $data['res']           = $res;
        $data['total']         = $this->city_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('location/view_city_list', $data);
    }

    /**
     * @param int|string $state_id
     */
    public function city_add($state_id = 0): string
    {
        [$state, $country] = $this->state_and_country($state_id);
        $this->for_cities($state);
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_city(null, (int) $country['country_id']);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'City added.');
                redirect_now('sitepanel/location/cities/' . (int) $this->request->getPost('state_id'));
            }
        }

        $data['heading_title'] = 'Add City';
        $data['breadcrumbs']   = $this->city_breadcrumbs($country, $state, 'Add City');
        $data['country']       = $country;
        $data['state']         = $state;
        $data['states']        = $this->state_model->get_state_options((int) $country['country_id']);
        $data['res']           = ['state_id' => $state['state_id'], 'city_name' => ''];

        return $this->render('location/view_city_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function city_edit($id = 0): string
    {
        $res               = $this->city_or_back($id);
        [$state, $country] = $this->state_and_country($res['state_id']);
        $this->for_cities($state);
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save_city($res, (int) $country['country_id']);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'City updated.');
                redirect_now('sitepanel/location/cities/' . (int) $this->request->getPost('state_id'));
            }
        }

        $data['heading_title'] = 'Edit City';
        $data['breadcrumbs']   = $this->city_breadcrumbs($country, $state, 'Edit City');
        $data['country']       = $country;
        $data['state']         = $state;
        $data['states']        = $this->state_model->get_state_options((int) $country['country_id']);
        $data['res']           = $res;

        return $this->render('location/view_city_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function city_status($id = 0): void
    {
        $this->require_post();
        $this->for_cities(['state_id' => $this->city_or_back($id)['state_id']]);
        $this->update_status(CityModel::TABLE, CityModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function city_delete($id = 0): void
    {
        $this->require_post();
        $this->for_cities(['state_id' => $this->city_or_back($id)['state_id']]);
        $this->delete_record(CityModel::TABLE, CityModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $state_id
     */
    public function city_bulk($state_id = 0): void
    {
        $this->require_post();
        [$state] = $this->state_and_country($state_id);
        $this->for_cities($state);
        [$keyword, $status] = $this->filters('post');
        $this->process_bulk_action($this->city_model, 'get_cities_by_ids', 'City', array_filter(['keyword' => $keyword, 'status' => $status]));
    }

    /* ---------------------------------------------------------------
     * Helpers
     * --------------------------------------------------------------- */

    /**
     * Messages and redirects of the shared actions go to this country's state list.
     */
    protected function for_states(array $country): void
    {
        $this->base                    = 'sitepanel/location/states/' . (int) $country['country_id'];
        $this->item                    = 'State';
        $this->state_model->country_id = (int) $country['country_id'];
    }

    /**
     * Messages and redirects of the shared actions go to this state's city list.
     */
    protected function for_cities(array $state): void
    {
        $this->base                 = 'sitepanel/location/cities/' . (int) $state['state_id'];
        $this->item                 = 'City';
        $this->city_model->state_id = (int) $state['state_id'];
    }

    /**
     * Delete one country or state with everything under it (the model's
     * bulk_delete), then back to the list with a message.
     */
    protected function delete_with_children(CountryModel|StateModel $model, array $row, string $key): never
    {
        $this->require_privilege(AdminAccess::PRIV_DELETE);

        $model->bulk_delete([(int) $row[$key]])
            ? $this->session->setFlashdata('sitepanel_success', $this->item . ' deleted.')
            : $this->session->setFlashdata('sitepanel_error', $this->item . ' could not be deleted. Please try again.');

        $this->back();
    }

    /**
     * @param int|string $id
     */
    protected function state_or_back($id): array
    {
        $this->item = 'State';

        return $this->record_or_back($this->state_model->get_state_by_id($id));
    }

    /**
     * [state, its country], or back to the list when either is missing.
     *
     * @param int|string $state_id
     */
    protected function state_and_country($state_id): array
    {
        $state      = $this->state_or_back($state_id);
        $this->item = 'Country';
        $country    = $this->record_or_back($this->country_model->get_country_by_id($state['country_id']));

        return [$state, $country];
    }

    /**
     * @param int|string $id
     */
    protected function city_or_back($id): array
    {
        $this->item = 'City';

        return $this->record_or_back($this->city_model->get_city_by_id($id));
    }

    /**
     * Location > Country > State [> $last].
     */
    protected function city_breadcrumbs(array $country, array $state, ?string $last = null): array
    {
        $crumbs = [
            'Location'       => site_url('sitepanel/location'),
            $country['name'] => site_url('sitepanel/location/states/' . (int) $country['country_id']),
        ];

        return $last === null
            ? $crumbs + [$state['state_name'] => null]
            : $crumbs + [$state['state_name'] => site_url('sitepanel/location/cities/' . (int) $state['state_id']), $last => null];
    }

    /**
     * [keyword, status] from the list query (get) or the posted list filters (post).
     */
    protected function filters(string $from): array
    {
        $keyword = $from === 'get' ? $this->request->getGet('keyword') : $this->request->getPost('keyword');
        $status  = (string) ($from === 'get' ? $this->request->getGet('status') : $this->request->getPost('status_filter'));

        return [mb_substr(trim((string) $keyword), 0, 80), in_array($status, ['1', '0'], true) ? $status : ''];
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save_country(?array $row): array
    {
        $except = $row ? (int) $row['country_id'] : 0;
        $v      = new FormValidation($this->request, $this);
        $v->set_rules(
            'name',
            'Country name',
            ['trim', 'required', 'max_length[128]', 'regex_match[' . self::NAME_PATTERN . ']',
                ['unique_country', fn ($value) => $this->country_model->name_exists(trim((string) $value), $except) === false]],
            ['regex_match' => "The {field} may only contain letters, spaces and . , ' ( ) - & /", 'unique_country' => 'The {field} already exists.'],
        );
        $v->set_rules('iso_code_2', 'ISO code (2 letters)', 'trim|exact_length[2]|alpha', ['alpha' => 'The {field} must be 2 letters.', 'exact_length' => 'The {field} must be 2 letters.']);
        $v->set_rules('iso_code_3', 'ISO code (3 letters)', 'trim|exact_length[3]|alpha', ['alpha' => 'The {field} must be 3 letters.', 'exact_length' => 'The {field} must be 3 letters.']);

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = [
            'name'       => (string) $this->request->getPost('name'),
            'iso_code_2' => strtoupper((string) $this->request->getPost('iso_code_2')),
            'iso_code_3' => strtoupper((string) $this->request->getPost('iso_code_3')),
        ];

        $saved = $row
            ? $this->country_model->safe_update(CountryModel::TABLE, $data, ['country_id' => (int) $row['country_id']])
            : (bool) $this->country_model->safe_insert(CountryModel::TABLE, $data + ['status' => '1']);

        return $saved ? [] : ['Country could not be saved. Please try again.'];
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save_state(?array $row): array
    {
        $except     = $row ? (int) $row['state_id'] : 0;
        $countries  = $this->country_model->get_country_options();
        $country_id = (int) $this->request->getPost('country_id');
        $v          = new FormValidation($this->request, $this);
        $v->set_rules(
            'country_id',
            'Country',
            ['required', ['valid_country', static fn ($value) => is_array($countries) && isset($countries[(int) $value])]],
            ['valid_country' => 'Choose a valid {field}.'],
        );
        $v->set_rules(
            'state_name',
            'State name',
            ['trim', 'required', 'max_length[150]', 'regex_match[' . self::NAME_PATTERN . ']',
                ['unique_state', fn ($value) => $this->state_model->name_exists($country_id, trim((string) $value), $except) === false]],
            ['regex_match' => "The {field} may only contain letters, spaces and . , ' ( ) - & /", 'unique_state' => 'The {field} already exists in this country.'],
        );

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = ['country_id' => $country_id, 'state_name' => (string) $this->request->getPost('state_name')];

        $saved = $row
            ? $this->state_model->safe_update(StateModel::TABLE, $data, ['state_id' => (int) $row['state_id']])
            : (bool) $this->state_model->safe_insert(StateModel::TABLE, $data + ['status' => '1']);

        return $saved ? [] : ['State could not be saved. Please try again.'];
    }

    /**
     * A city may move to another state of the same country.
     *
     * @return list<string> Errors (empty = saved)
     */
    protected function save_city(?array $row, int $country_id): array
    {
        $except   = $row ? (int) $row['city_id'] : 0;
        $states   = $this->state_model->get_state_options($country_id);
        $state_id = (int) $this->request->getPost('state_id');
        $v        = new FormValidation($this->request, $this);
        $v->set_rules(
            'state_id',
            'State',
            ['required', ['valid_state', static fn ($value) => is_array($states) && isset($states[(int) $value])]],
            ['valid_state' => 'Choose a valid {field}.'],
        );
        $v->set_rules(
            'city_name',
            'City name',
            ['trim', 'required', 'max_length[150]', 'regex_match[' . self::NAME_PATTERN . ']',
                ['unique_city', fn ($value) => $this->city_model->name_exists($state_id, trim((string) $value), $except) === false]],
            ['regex_match' => "The {field} may only contain letters, spaces and . , ' ( ) - & /", 'unique_city' => 'The {field} already exists in this state.'],
        );

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = ['state_id' => $state_id, 'city_name' => (string) $this->request->getPost('city_name')];

        $saved = $row
            ? $this->city_model->safe_update(CityModel::TABLE, $data, ['city_id' => (int) $row['city_id']])
            : (bool) $this->city_model->safe_insert(CityModel::TABLE, $data + ['status' => '1']);

        return $saved ? [] : ['City could not be saved. Please try again.'];
    }
}
