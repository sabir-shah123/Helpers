<?php
function setCustomFields($request)
{
$user_custom_fields = new \stdClass;
try {
$request_array = [];

foreach ($request as $key => $value) {
$key = str_replace([' ', '_'], '', $key);
$key = strtolower($key);
$request_array[$key] = $value;
}

$ghl_custom_values = ghl_api_call('custom-fields');
$custom_values = $ghl_custom_values;

if (property_exists($custom_values, 'customFields')) {
$custom_values = $custom_values->customFields;
$custom_values = array_filter($custom_values, function ($value) use ($request_array) {
$kn = strtolower(str_replace(' ', '_', $value->name));
return in_array($kn, array_keys($request_array));
});

foreach ($custom_values as $key => $custom) {
$key = str_replace([' ', '_'], '', $custom->name);
$key = strtolower($key);
$custom->value = $request_array[$key];
$request_array[$key] = $custom;
}

$i = 0;
$v = 0;
$all_keys = array_keys($request);
foreach ($request_array as $key => $custom) {
$i++;
$value = '';
$title = $all_keys[$v];
$id = null;
$lttitle = strtolower($title);
$type = strpos($lttitle, 'date') !== false ? 'DATE' : 'TEXT';
if ($type == 'TEXT') {
$type = strpos($lttitle, 'amount') !== false || strpos($key, 'total_paid') !== false || strpos($lttitle, 'grand_total') !== false ? 'MONETORY' : 'TEXT';
}

$name = str_replace('_', ' ', $title);

$name = ucwords($name);


if (is_object($custom)) {
$id = $custom->id;
$value = $custom->value;
if (is_object($value)) {
$value = $value->value ?? '';
}
if ($custom->name != $name) {
if ($i % 5 == 0) {
sleep(2);
}
$abc = ghl_api_call('custom-fields/' . $custom->id, 'PUT', json_encode(['name' => $name, 'dataType' => $type]), [], true);
}
} else {
if ($i % 5 == 0) {
sleep(2);
}
$value = $custom;
if (is_object($value)) {
$value = $value->value ?? '';
}
$abc = ghl_api_call('custom-fields/', 'POST', json_encode(['name' => $name, 'dataType' => $type]), [], true);

$cord = json_decode($abc);
if ($cord && property_exists($cord, 'id')) {
$id = $cord->id;
}
}
$v++;
if ($id) {
$user_custom_fields->$id = $value;
}
}
}
} catch (\Exception $e) {
}
return $user_custom_fields;
}
