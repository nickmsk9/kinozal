<?php

declare(strict_types=1);

/*
    Информация для разработчика

    Все функции возвращают небольшой массив $return.

    $return[0] — данные, которые должна вернуть функция.
    $return[1] — смещение по всей bencode-строке, откуда начинается следующий блок данных.

    numberdecode() возвращает:
    $return[0] — прочитанное число;
    $return[1] — позицию следующего символа после завершителя.

    numberdecode() используется как для чисел вида i11e,
    так и для строк вида 11:hello there, поэтому допускает завершители "e" и ":".

    decodelist() возвращает:
    $return[0] — обычный индексированный массив;
    $return[1] — позицию следующего полезного байта после закрывающего "e".

    decodeDict() возвращает:
    $return[0] — ассоциативный массив.
    Например:
    $return[0]["announce"] = "http://www.whatever.com:6969/announce";

    decodeEntry() возвращает:
    - число, если текущий элемент вида i12345e;
    - строку, если текущий элемент вида 11:hello there;
    - массив, если найден словарь "d" или список "l".

    Исправлено под PHP 8+:
    - добавлены проверки выхода за границы строки;
    - убраны обращения к несуществующим offset;
    - убран addslashes() для ключей массива;
    - пустой словарь возвращается как пустой массив, а не true;
    - сохранены старые имена класса и функции для совместимости.
*/

class BDecode
{
    private function charAt(string $wholefile, int $offset): ?string
    {
        return ($offset >= 0 && $offset < strlen($wholefile))
            ? $wholefile[$offset]
            : null;
    }

    public function numberdecode(string $wholefile, int $start): array
    {
        $ret = [0, 0];
        $offset = $start;
        $negative = false;

        $char = $this->charAt($wholefile, $offset);

        if ($char === null) {
            return [false];
        }

        // Обработка отрицательных чисел
        if ($char === '-') {
            $negative = true;
            $offset++;

            $char = $this->charAt($wholefile, $offset);

            if ($char === null) {
                return [false];
            }
        }

        // Обработка нуля
        if ($char === '0') {
            $offset++;

            if ($negative) {
                return [false];
            }

            $next = $this->charAt($wholefile, $offset);

            if ($next === ':' || $next === 'e') {
                $ret[0] = 0;
                $ret[1] = $offset + 1;

                return $ret;
            }

            return [false];
        }

        while (true) {
            $char = $this->charAt($wholefile, $offset);

            if ($char === null) {
                return [false];
            }

            if ($char >= '0' && $char <= '9') {
                $ret[0] *= 10;
                $ret[0] += ord($char) - ord('0');
                $offset++;

                continue;
            }

            // Допускаем ":" и "e", потому что функция используется и для чисел, и для строк
            if ($char === 'e' || $char === ':') {
                $ret[1] = $offset + 1;

                if ($negative) {
                    if ($ret[0] == 0) {
                        return [false];
                    }

                    $ret[0] = -$ret[0];
                }

                return $ret;
            }

            return [false];
        }
    }

    public function decodeEntry(string $wholefile, int $offset = 0): array
    {
        $char = $this->charAt($wholefile, $offset);

        if ($char === null) {
            return [false];
        }

        if ($char === 'd') {
            return $this->decodeDict($wholefile, $offset);
        }

        if ($char === 'l') {
            return $this->decodeList($wholefile, $offset);
        }

        if ($char === 'i') {
            return $this->numberdecode($wholefile, $offset + 1);
        }

        // Строка: сначала читаем длину, затем берём указанное количество байт
        $info = $this->numberdecode($wholefile, $offset);

        if (!isset($info[0], $info[1]) || $info[0] === false) {
            return [false];
        }

        $stringLength = (int)$info[0];
        $stringOffset = (int)$info[1];

        if ($stringLength < 0) {
            return [false];
        }

        if ($stringOffset + $stringLength > strlen($wholefile)) {
            return [false];
        }

        $ret = [];
        $ret[0] = substr($wholefile, $stringOffset, $stringLength);
        $ret[1] = $stringOffset + strlen($ret[0]);

        return $ret;
    }

    public function decodeList(string $wholefile, int $start): array
    {
        $offset = $start + 1;
        $i = 0;

        if ($this->charAt($wholefile, $start) !== 'l') {
            return [false];
        }

        $ret = [];

        while (true) {
            $char = $this->charAt($wholefile, $offset);

            if ($char === null) {
                return [false];
            }

            if ($char === 'e') {
                break;
            }

            $value = $this->decodeEntry($wholefile, $offset);

            if (!isset($value[0], $value[1]) || $value[0] === false) {
                return [false];
            }

            $ret[$i] = $value[0];
            $offset = (int)$value[1];
            $i++;
        }

        $final = [];
        $final[0] = $ret;
        $final[1] = $offset + 1;

        return $final;
    }

    public function decodeDict(string $wholefile, int $start = 0): array
    {
        $offset = $start;

        if ($this->charAt($wholefile, $offset) === 'l') {
            return $this->decodeList($wholefile, $start);
        }

        if ($this->charAt($wholefile, $offset) !== 'd') {
            return [false];
        }

        $ret = [];
        $offset++;

        while (true) {
            $char = $this->charAt($wholefile, $offset);

            if ($char === null) {
                return [false];
            }

            if ($char === 'e') {
                $offset++;
                break;
            }

            $left = $this->decodeEntry($wholefile, $offset);

            if (!isset($left[0], $left[1]) || $left[0] === false || !is_string($left[0])) {
                return [false];
            }

            $offset = (int)$left[1];

            $value = $this->decodeEntry($wholefile, $offset);

            if (!isset($value[0], $value[1]) || $value[0] === false) {
                return [false];
            }

            /*
                addslashes() убран специально.
                Декодер не должен менять ключи.
                Экранирование нужно делать отдельно при выводе в HTML или записи в SQL.
            */
            $ret[$left[0]] = $value[0];
            $offset = (int)$value[1];
        }

        $final = [];
        $final[0] = $ret;
        $final[1] = $offset;

        return $final;
    }
}

// Использовать эту функцию, как и раньше:
// BDecode("d8:announce44:http://www...e");
function BDecode(string $wholefile): mixed
{
    $decoder = new BDecode();
    $return = $decoder->decodeEntry($wholefile);

    return $return[0] ?? false;
}

?>