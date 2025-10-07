<?php
namespace OpenCCK\Infrastructure\API;

use OpenCCK\Infrastructure\API\Input\Filter;
use AllowDynamicProperties;

/**
 * Input validation
 * @package API
 */
#[AllowDynamicProperties]
final class Input {
    /**
     * Filters
     * @var array
     */
    private static $FILTERS = [
        Filter::BOOLEAN => [FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]],
        Filter::INTEGER => [FILTER_VALIDATE_INT],
        Filter::INT => [FILTER_VALIDATE_INT],
        Filter::FLOAT => [FILTER_VALIDATE_FLOAT],
        Filter::STRING => [FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]],
        Filter::STR => [FILTER_SANITIZE_STRING, ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]],
        Filter::EMAIL => [FILTER_VALIDATE_EMAIL],
        Filter::URL => [FILTER_VALIDATE_URL],
        Filter::IP => [FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE]],
        Filter::RAW => [FILTER_UNSAFE_RAW],
        Filter::ARRAY => [],
        Filter::OBJECT => [],
    ];

    /**
     * Input constructor
     * @param array $items
     */
    public function __construct(array $items = []) {
        foreach ($items as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get value from input
     * @param string $key
     * @param ?mixed $default
     * @param string $filter
     * @return mixed
     */
    public function get(string $key, mixed $default = null, string $filter = 'string'): mixed {
        // prettier-ignore
        return $this->filter(
			$this->{$key} ?? $default,
			$filter
		);
    }

    /**
     * Filter value
     * @param mixed $value
     * @param string $type
     * @param array $options
     * @return mixed
     */
    public function filter(mixed $value, string $type, array $options = []): mixed {
        if (isset(Input::$FILTERS[$type]) && isset(Input::$FILTERS[$type][0])) {
            $filter = Input::$FILTERS[$type][0];
            $options = (count($options) ? $options : isset(Input::$FILTERS[$type][1])) ? Input::$FILTERS[$type][1] : [];
        } else {
            $filter = Input::$FILTERS['raw'][0];
        }

        // type casting
        if (!(is_object($value) || is_array($value))) {
            switch ($type) {
                case 'array':
                    return (array) $value;
                case 'object':
                    return (object) $value;
                case 'boolean':
                    return filter_var($value, $filter, $options);
                case 'raw':
                    return $value;
                case 'integer':
                case 'int':
                    $value = filter_var(is_bool($value) ? (int) $value : $value, $filter, $options);
                    return $value === false ? null : $value;
                default:
                    $value = filter_var($value, $filter, $options);
                    return $value === false ? null : $value;
            }
        } else {
            switch ($type) {
                case 'raw':
                    return $value;
                case 'array':
                    if (!is_array($value)) {
                        $value = (array) $value;
                    }
                    return $value;
                case 'object':
                    if (!is_object($value)) {
                        $value = (object) $value;
                    }
                    return $value;
                default:
                    break;
            }
        }
        return null;
    }

    /**
     * Set value in input
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void {
        $this->{$key} = $value;
    }

    /**
     * Delete value in input
     * @param string $key
     * @return void
     */
    public function delete(string $key): void {
        unset($this->{$key});
    }
}
