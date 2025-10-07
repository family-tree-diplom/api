<?php

namespace OpenCCK\App\Helper;

use Exception;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\Input;

final class Helper {
    /**
     * @param Input $params
     * @param string $key
     * @param array $default
     * @return array
     */
    public function generateSortOrderMap(
        Input $params,
        string $key,
        array $default = ['key' => 'id', 'order' => 'asc']
    ): array {
        return array_reduce(
            $params->get($key, [(object) $default], Input\Filter::ARRAY),
            function ($carry, $item) {
                $carry[$item->key] = $item->order;
                return $carry;
            },
            []
        );
    }

    /**
     * @param array $params
     * @param array|null $keys
     * @return string
     * @throws Exception
     */
    public function generateHash(array $params, ?array $keys = null): string {
        $keys = $keys ?? array_keys($params);
        $stack = [];
        foreach ($keys as $key) {
            $stack[] = match ($key) {
                'date' => date_format(new \DateTime($params[$key]), 'Y-m-d'),
                default => $params[$key],
            };
        }
        return md5(serialize($stack));
    }

    /**
     * @param string $str
     * @return string
     */
    public function transliterateString(string $str): string {
        // prettier-ignore
        $tr = [
            "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g","Д"=>"d","Е"=>"e","Ё"=>"yo","Ж"=>"zh","З"=>"z","И"=>"i","Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n","О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t","У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch","Ш"=>"sh","Щ"=>"shch","Ъ"=>"","Ы"=>"y","Ь"=>"","Э"=>"e","Ю"=>"yu","Я"=>"ya",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"shch",	"ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
            " "=>"-","-"=>"-",
            "A"=>"a","B"=>"b","C"=>"c","D"=>"d","E"=>"e","F"=>"f","G"=>"g","H"=>"h","I"=>"i","J"=>"j","K"=>"k","L"=>"l","M"=>"m","N"=>"n","O"=>"o","P"=>"p","Q"=>"q","R"=>"r","S"=>"s","T"=>"t","U"=>"u","V"=>"v","W"=>"w","X"=>"x","Y"=>"y","Z"=>"z",
            "a"=>"a","b"=>"b","c"=>"c","d"=>"d","e"=>"e","f"=>"f","g"=>"g","h"=>"h","i"=>"i","j"=>"j","k"=>"k","l"=>"l","m"=>"m","n"=>"n","o"=>"o","p"=>"p",
            "q"=>"q","r"=>"r","s"=>"s","t"=>"t","u"=>"u","v"=>"v","w"=>"w","x"=>"x","y"=>"y","z"=>"z",
            "0"=>"0","1"=>"1","2"=>"2","3"=>"3","4"=>"4","5"=>"5","6"=>"6","7"=>"7","8"=>"8","9"=>"9",
            "."=> "","/"=> "",","=>"","("=>"",")"=>"","["=>"","]"=>"","="=>"","+"=>"",
            "*"=>"","?"=>"","\""=>"","'"=>"","&"=>"","%"=>"","#"=>"","@"=>"","!"=>"",";"=>"","№"=>"n",
            "^"=>"",":"=>"","~"=>"","\\"=>"", "`"=>"", "«"=>"", "»"=>"", "—"=>"-", "’"=>"", "…"=>""
        ];

        $pseudo = preg_replace('/-+/', '-', strtr(strtolower($str), $tr));
        return preg_replace('/[^a-z0-9-]/', '', $pseudo);
    }

    public function cyrillicToLatin(string $input): string {
        // prettier-ignore
        return strtr($input, [
            'А' => 'A', 'а' => 'a',
            'В' => 'B', 'в' => 'b',
            'Е' => 'E', 'е' => 'e',
            'К' => 'K', 'к' => 'k',
            'М' => 'M', 'м' => 'm',
            'Н' => 'H', 'н' => 'h',
            'О' => 'O', 'о' => 'o',
            'Р' => 'P', 'р' => 'p',
            'С' => 'C', 'с' => 'c',
            'Т' => 'T', 'т' => 't',
            'У' => 'Y', 'у' => 'y',
            'Х' => 'X', 'х' => 'x',
        ]);
    }

    public function latinToCyrillic(string $input): string {
        // prettier-ignore
        return strtr($input, [
            'A' => 'А', 'a' => 'а',
            'B' => 'В', 'b' => 'в',
            'E' => 'Е', 'e' => 'е',
            'K' => 'К', 'k' => 'к',
            'M' => 'М', 'm' => 'м',
            'H' => 'Н', 'h' => 'н',
            'O' => 'О', 'o' => 'о',
            'P' => 'Р', 'p' => 'р',
            'C' => 'С', 'c' => 'с',
            'T' => 'Т', 't' => 'т',
            'Y' => 'У', 'y' => 'у',
            'X' => 'Х', 'x' => 'х',
        ]);
    }

    /**
     * @param array $inputArray
     * @param string $keyPattern
     * @return array
     */
    public function extractPropertiesByKey(array $inputArray, string $keyPattern = 'filter'): array {
        $filteredArray = [];
        foreach ($inputArray as $key => $value) {
            if (preg_match('/' . $keyPattern . '\[(.*?)\]$/', $key, $matches)) {
                $filteredArray[$matches[1]] = $value;
            }
        }
        return $filteredArray;
    }

    function pattern(string $string, array $patterns): string {
        foreach ($patterns as $pk => $pv) {
            $string = str_replace('{' . $pk . '}', $pv, $string);
        }
        return $string;
    }
}
