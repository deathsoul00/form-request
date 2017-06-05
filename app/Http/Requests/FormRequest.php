<?php
/**
 * A base class for FormRequest with implementation of zend-filter classes
 * it allows developer to filter the incoming request inputs before the validaiton
 * it also supports a custom class that extends the `Zend\Filter\AbstractFilter` class and a method `filter` is callable
 *
 * filters format
 *
 * [
 *     '{field}' => {filter_class_name}|[
 *         {filter_class_name_1} => [
 *             '{option1}' => '{option_value}'
 *         ]
 *     ]
 * ]
 *
 * @author Mike Alvarez <michaeljpalvarez@gmail.com>
 *
 */
namespace App\Http\Requests;

use Zend\Filter\AbstractFilter;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

class FormRequest extends BaseFormRequest
{
    /**
     * for reference classes use https://framework.zend.com/manual/2.0/en/modules/zend.filter.set.html
     *
     * @var array
     */
    protected $filters = [];

    /**
     * error response message
     *
     * @var string
     */
    protected $message = '';

    /**
     * wrapper for Illuminate\Foundation\Http\FormRequest::all
     *
     * @return array
     */
    public function all()
    {
        return $this->_sanitize(parent::all());
    }

    /**
     * override \Illuminate\Foundation\Http\FormRequest::input
     *
     * @param  string $key     key of the input needed
     * @param  mixed $default  default value to return if no value of the given key is available
     * @return mixed           returns the value for the given key or the default value provided
     */
    public function input($key = null, $default = null)
    {
        $inputs = $this->getInputSource()->all() + $this->query->all();

        // replace input with filtered input
        $inputs = $this->_sanitize($inputs);

        if (is_null($key)) {
            return $inputs;
        }

        return data_get($inputs, $key, $default);
    }

    /**
     * override \Illuminate\Foundation\Http\FormRequest::query
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function query($key = null, $default = null)
    {
        // replace input with filtered input
        $query = $this->_sanitize($this->query->all());

        if (is_null($key)) {
            return $query;
        }

        return data_get($query, $key, $default);
    }

    /**
     * get filters defined
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * fitler
     * @param  array  $inputs array of inputs/requests
     * 
     * @return array          array sanitized input
     */
    private function _sanitize(array $inputs)
    {
        // check if there are valid inputs else return empty array
        if (!count($inputs)) {
            return [];
        }

        // dump ($inputs); exit;

        // get the filters defined
        $filters = $this->getFilters();

        // check if filters is an array
        if (!is_array($filters)) {
            throw new \InvalidArgumentException('filters only accepts array as its value');
        }

        if ($filters) {
            foreach ($filters as $field => $filter_class) {
                $value = data_get($inputs, $field);
                if (isset($value)) {
                    if (is_array($filter_class)) {
                        // support for chain filtering and filter with options
                        foreach ($filter_class as $chain_filter_class => $option) {
                            // added support for zend-filter class that has options
                            if (is_int($chain_filter_class)) {
                                $chain_filter_class = $option;
                                $option = [];
                            }
                            $value = $this->_getFilteredValue($value, $chain_filter_class, $option);
                        }
                    } elseif (is_string($filter_class)) {
                        $value = $this->_getFilteredValue($value, $filter_class);
                    }

                    data_set($inputs, $field, $value);
                }
            }
        }

        return $inputs;
    }

    /**
     * calls the filter class defined for each field and returns its filtered value
     *
     * @param  string $filter_class
     * @param  mixed  $value
     * @param  array  $option
     * @return mixed
     */
    private function _runFilterClass($filter_class, $value, array $options = [])
    {

        $filter_class = sprintf('\\%s', ltrim($filter_class, '\\'));
        $filter = new $filter_class($options);

        // check if filter is an instance of Zend\Filter\AbstractFilter
        if (!$filter instanceOf AbstractFilter || !is_callable([$filter, 'filter'])) {
            throw new \InvalidArgumentException('filter %s must be an instance of Zend\\Filter\\AbstractFilter or a callable `filter` method', get_class($filter));
        } else {
           $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * get filtered value using the filter class provided
     *
     * @param  mixed  $value         value to be filtered
     * @param  string $filter_class  instance of AbstractFilter class name
     * @param  array  $options       options to be passed to the filter class
     * @return mixed                 filtered value
     */
    private function _getFilteredValue($value, $filter_class, array $options = [])
    {
        if (is_array($value)) {
            // filter all the content of the array using array_map
            return array_map(function($content) use ($filter_class, $options) {
                return $this->_runFilterClass($filter_class, $content, $options);
            }, $value);
        }

        return $this->_runFilterClass($filter_class, $value, $options);
    }
}