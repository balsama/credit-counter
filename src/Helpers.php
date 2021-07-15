<?php

namespace Balsama;

class Helpers
{

    /**
     * Shifts each top level key of an array of arrays into the row's contained array while preserving top-level keys.
     *
     * @example
     *   Given
     *     [
     *       'a' => 'foo',
     *       'b' => 'bar',
     *     ]
     *   Returns
     *     [
     *       'a' => ['a', 'foo'],
     *       'b' => ['b', 'bar'],
     *     ]
     *
     * @param  array[] | string[] $array
     * @return array[]
     */
    public static function includeArrayKeysInArray(array $array)
    {
        $newArray = [];
        foreach ($array as $key => $row) {
            if (is_array($row)) {
                array_unshift($row, $key);
                $newArray[$key] = $row;
            } elseif (is_string($row) || is_int($row)) {
                $newArray[$key] = [$key, $row];
            } else {
                throw new \InvalidArgumentException('Expected each row in the array to be an array, string, or int.');
            }
        }

        return $newArray;
    }

    /**
     * Writes an array of arrays to a CSV file.
     *
     * @param string[] $headers
     *   The names of the table columns. Pass an empty array if you don't want any headers (e.g. if you're appending to
     *   an existing file.
     * @param array[] $data
     *   Data to write. Each top-level array should contain an array the same length as the $header array.
     * @param string $filename
     * @param bool $append
     *   Whether to append to the file if it exist or overwrite from the beginning of the file.
     * @param string $path
     */
    public static function csv(array $headers, array $data, string $filename, $append = false, $path = 'data/')
    {
        if ($headers && $data) {
            if (count($headers) !== count(reset($data))) {
                throw new \InvalidArgumentException(
                    'The length of the `$header` array must equal the length of each of the arrays in `$data`'
                );
            }
        }

        $mode = ($append) ? 'a' : 'w';

        $fp = fopen($path . $filename, $mode);
        if ($headers) {
            fputcsv($fp, $headers);
        }
        foreach ($data as $datum) {
            fputcsv($fp, $datum);
        }
        fclose($fp);
    }

}