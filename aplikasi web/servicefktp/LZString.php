<?php
// File: LZString.php
// Fungsi: Library untuk dekompresi respon BPJS (Porting dari LZString JS/Java)

class LZString {
    public static function decompressFromEncodedURIComponent($input) {
        if ($input == null) return "";
        $input = str_replace(" ", "+", $input);
        return self::_decompress(strlen($input), 32, function($index) use ($input) { 
            return self::_getBaseValue(self::$keyStrUriSafe, $input[$index]); 
        });
    }

    private static $keyStrUriSafe = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-$";
    private static $baseReverseDic = [];

    private static function _getBaseValue($alphabet, $character) {
        if (!isset(self::$baseReverseDic[$alphabet])) {
            self::$baseReverseDic[$alphabet] = array_flip(str_split($alphabet));
        }
        return isset(self::$baseReverseDic[$alphabet][$character]) ? self::$baseReverseDic[$alphabet][$character] : 0;
    }

    private static function _decompress($length, $resetValue, $getNextValue) {
        $dictionary = [];
        $enlargeIn = 4;
        $dictSize = 4;
        $numBits = 3;
        $entry = "";
        $result = [];
        $data = ['val' => $getNextValue(0), 'position' => $resetValue, 'index' => 1];

        for ($i = 0; $i < 3; $i += 1) {
            $dictionary[$i] = $i;
        }

        $bits = 0;
        $maxpower = pow(2, 2);
        $power = 1;
        while ($power != $maxpower) {
            $resb = $data['val'] & $data['position'];
            $data['position'] >>= 1;
            if ($data['position'] == 0) {
                $data['position'] = $resetValue;
                $data['val'] = $getNextValue($data['index']++);
            }
            $bits |= ($resb > 0 ? 1 : 0) * $power;
            $power <<= 1;
        }

        switch ($next = $bits) {
            case 0:
                $bits = 0;
                $maxpower = pow(2, 8);
                $power = 1;
                while ($power != $maxpower) {
                    $resb = $data['val'] & $data['position'];
                    $data['position'] >>= 1;
                    if ($data['position'] == 0) {
                        $data['position'] = $resetValue;
                        $data['val'] = $getNextValue($data['index']++);
                    }
                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }
                $c = chr($bits);
                break;
            case 1:
                $bits = 0;
                $maxpower = pow(2, 16);
                $power = 1;
                while ($power != $maxpower) {
                    $resb = $data['val'] & $data['position'];
                    $data['position'] >>= 1;
                    if ($data['position'] == 0) {
                        $data['position'] = $resetValue;
                        $data['val'] = $getNextValue($data['index']++);
                    }
                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }
                $c = chr($bits);
                break;
            case 2:
                return "";
        }
        $dictionary[3] = $c;
        $w = $c;
        $result[] = $c;
        
        while (true) {
            if ($data['index'] > $length) {
                return "";
            }

            $bits = 0;
            $maxpower = pow(2, $numBits);
            $power = 1;
            while ($power != $maxpower) {
                $resb = $data['val'] & $data['position'];
                $data['position'] >>= 1;
                if ($data['position'] == 0) {
                    $data['position'] = $resetValue;
                    $data['val'] = $getNextValue($data['index']++);
                }
                $bits |= ($resb > 0 ? 1 : 0) * $power;
                $power <<= 1;
            }

            switch ($c = $bits) {
                case 0:
                    $bits = 0;
                    $maxpower = pow(2, 8);
                    $power = 1;
                    while ($power != $maxpower) {
                        $resb = $data['val'] & $data['position'];
                        $data['position'] >>= 1;
                        if ($data['position'] == 0) {
                            $data['position'] = $resetValue;
                            $data['val'] = $getNextValue($data['index']++);
                        }
                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }

                    $dictionary[$dictSize++] = chr($bits);
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 1:
                    $bits = 0;
                    $maxpower = pow(2, 16);
                    $power = 1;
                    while ($power != $maxpower) {
                        $resb = $data['val'] & $data['position'];
                        $data['position'] >>= 1;
                        if ($data['position'] == 0) {
                            $data['position'] = $resetValue;
                            $data['val'] = $getNextValue($data['index']++);
                        }
                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }
                    $dictionary[$dictSize++] = chr($bits);
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 2:
                    return implode("", $result);
            }

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }

            if (isset($dictionary[$c])) {
                $entry = $dictionary[$c];
            } else {
                if ($c === $dictSize) {
                    $entry = $w . $w[0];
                } else {
                    return null;
                }
            }
            $result[] = $entry;

            $dictionary[$dictSize++] = $w . $entry[0];
            $enlargeIn--;

            $w = $entry;

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }
        }
    }
}
?>