<?php

/**
* @url http://stackoverflow.com/questions/6661530/php-multi-dimensional-array-search
*/
function searchForId($id, $array, $key='name') {
foreach ($array as $k => $val) {
if ($val[$key] === $id) {
return $k;
}
}
return null;
}

/**
* Glorified ternary, returns a (formatted) value if the value is not empty, or return an alternative string (default empty)
* @param mixed $dataToCheck value
* @param mixed $dataToReturn formatted value, first argument returned if null
* @param mixed $empty value to return if empty, default : empty string
* @return mixed
*/
function getEmptyOrFormat($dataToCheck, $dataToReturn=null, $empty='') {
return empty($dataToCheck) ? $empty : (is_null($dataToReturn) ? $dataToCheck : $dataToReturn);
}