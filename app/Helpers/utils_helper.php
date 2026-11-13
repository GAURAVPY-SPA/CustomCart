<?php

/**
 * General-purpose utility functions (autoloaded, see Config\Autoload::$helpers),
 * framework- and business-independent.
 *
 * Not included:
 *  - functions this project already has: trace() (app_helper),
 *    char_limiter() (character_limiter()), get_Youtube_Id() (bp_youtube_id()),
 *    removeImage() / make_missing_folder() (App\Libraries\ImageUpload);
 *  - getAmountInWords(): Indian Rupees / Lakh / Crore wording, not for AED.
 */

if (! function_exists('getDateFormat')) {
    /**
     * A date/time string in one of the reference formats:
     *   1  06 Dec, 2010                 2  06 December, 2010
     *   3  Mon Dec 06, 2010             4  Monday December 06, 2010
     *   5  Thursday April 17, 1975, 03:04:00
     *   6  Thursday April 17, 1975, 08:28 AM
     *   7  17 Apr 1975, 08:28 AM        8  17 Apr, 1975, 03:04
     *   9  Apr 17, 1975 [Thursday]     10  April 1975
     *  11  25 (day of month)
     * ($separator replaces the ',' in each format.) An unknown format or an
     * unreadable date gives ''.
     *
     * @param string $date Any date strtotime() reads, e.g. '2026-10-27 15:04:00'
     */
    function getDateFormat($date, $format, $separator = ','): string
    {
        $time = strtotime((string) $date);
        if ($time === false) {
            return '';
        }

        $patterns = [
            1  => 'd M' . $separator . ' Y',
            2  => 'd F' . $separator . ' Y',
            3  => 'D M d' . $separator . ' Y',
            4  => 'l F d' . $separator . ' Y',
            5  => 'l F d' . $separator . ' Y' . $separator . ' h:i:s',
            6  => 'l F d' . $separator . ' Y' . $separator . ' H:i A',
            7  => 'd M Y' . $separator . ' H:i A',
            8  => 'd M' . $separator . ' Y' . $separator . ' h:i',
            9  => 'M d' . $separator . ' Y [l]',
            10 => 'F Y',
            11 => 'd',
        ];

        return isset($patterns[(int) $format]) ? date($patterns[(int) $format], $time) : '';
    }
}

if (! function_exists('humanTiming')) {
    /**
     * How long ago (or ahead) a Unix timestamp is, in its largest whole unit:
     * '3 days', '1 hour', '1 second'. (The reference read "now" from a
     * CodeIgniter 3 config item; this uses the current time.)
     */
    function humanTiming($time): string
    {
        $diff   = abs(time() - (int) $time);
        $tokens = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second',
        ];

        foreach ($tokens as $unit => $text) {
            if ($diff < $unit) {
                continue;
            }
            $count = (int) round($diff / $unit);

            return $count . ' ' . $text . ($count > 1 ? 's' : '');
        }

        return '1 seconds';
    }
}

if (! function_exists('array_flatten')) {
    /**
     * The scalar values of a nested array, appended to $return in order, e.g.
     * array_flatten([1, [2, [3]]], []) gives [1, 2, 3]. With a non-empty $key,
     * values are stored under their own (innermost) keys instead.
     */
    function array_flatten($array, array $return = [], $key = ''): array
    {
        if (is_array($array)) {
            foreach ($array as $k => $value) {
                $return = array_flatten($value, $return, $key !== '' ? $k : '');
            }
        } elseif ($key !== '') {
            $return[$key] = $array;
        } else {
            $return[] = $array;
        }

        return $return;
    }
}

if (! function_exists('getArrayValueBykey')) {
    /**
     * The values of $values for each non-empty key listed in $keys, in that
     * order; keys missing from $values are skipped.
     */
    function getArrayValueBykey($keys, $values): array
    {
        $result = [];

        if (is_array($keys) && is_array($values)) {
            foreach ($keys as $key) {
                if ($key !== '' && $key !== null && array_key_exists($key, $values)) {
                    $result[] = $values[$key];
                }
            }
        }

        return $result;
    }
}

if (! function_exists('getAge')) {
    /**
     * Age in years from a date of birth, counted as in the reference (years
     * between the birth date and the start of the current year, plus one);
     * 0 when the date is unreadable or not in the past.
     */
    function getAge($dob): int
    {
        $birth   = strtotime((string) $dob);
        $current = strtotime(date('Y') . '-01-01');

        if ($birth === false || $current <= $birth) {
            return 0;
        }

        return (int) round(($current - $birth) / 31536000) + 1;
    }
}

if (! function_exists('check_spam_words')) {
    /**
     * Whether $text contains any of the listed words as a whole word
     * (case-insensitive). The words are matched literally.
     *
     * @param list<string> $words
     */
    function check_spam_words($words, $text): bool
    {
        if (! is_array($words) || (string) $text === '') {
            return false;
        }

        foreach ($words as $word) {
            $word = trim((string) $word);
            if ($word !== '' && preg_match('/\b' . preg_quote($word, '/') . '\b/iu', (string) $text) === 1) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('file_ext')) {
    /**
     * Lower-case extension of a file name without the dot ('' when none),
     * e.g. file_ext('Photo.JPG') gives 'jpg'.
     */
    function file_ext($file): string
    {
        $ext = strrchr((string) $file, '.');

        return $ext === false ? '' : strtolower(substr($ext, 1));
    }
}

if (! function_exists('applyFilter')) {
    /**
     * Normalise a numeric request value:
     *  'NUMERIC_GT_ZERO'  a whole number above zero (leading zeros dropped), else 0;
     *  'NUMERIC_WT_ZERO'  digits only (zero allowed) returned as given, else -1.
     * Any other type gives null.
     *
     * @return int|string|null
     */
    function applyFilter($type, $value)
    {
        switch ($type) {
            case 'NUMERIC_GT_ZERO':
                $value = ltrim(trim((string) $value), '0');

                return preg_match('/^[1-9][0-9]*$/D', $value) === 1 ? $value : 0;

            case 'NUMERIC_WT_ZERO':
                return preg_match('/^[0-9]*$/D', trim((string) $value)) === 1 ? $value : -1;
        }

        return null;
    }
}
