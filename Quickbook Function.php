<?php

function checkQuickbook($invoice_data = null, $key = false)
{

    if ($key) {
        $invoice_data = $invoice_data->getinvoice_data();
    }
    $quickbook = check_object($invoice_data, 'quickbook', null);
    $quickbook_invoice = null;
    if ($quickbook) {
        $qbid = $quickbook->id;
        $qbdata = getQBInvoice($qbid); //EInvoiceStatus
        // dd($qbdata);
        $quickbook_invoice = $qbdata['data'];
    }
    return $quickbook_invoice;
}

function updateInvoiceStatus($invoice, $quickbook_invoice = null)
{
    try {
        if ($quickbook_invoice) {

            if ($invoice->status == 'Unpaid' && isset($quickbook_invoice['EInvoiceStatus']) && $quickbook_invoice['EInvoiceStatus'] == 'Paid') {
                $invoice->status = 'Paid';
            }
            if ($invoice->grand_total != $quickbook_invoice['TotalAmt']) {
                $invoice->grand_total = $quickbook_invoice['TotalAmt'];
            }
            $invoice->save();
        }
    } catch (\Exception $e) {
    }
    return $invoice;
}



function qbVersion($t = '?')
{
    return $t . 'minorversion=65';
}
function getQBCustomer($id)
{
    $cust = quickbook_api_call('customer/' . $id . qbVersion());

    if (!isset($cust['Customer'])) {
        return null;
    }
    $company_id = session('company_id');
    $customer = $cust['Customer'];
    $name = $customer['DisplayName'];
    $email = '';
    $phone = '';
    try {
        $phone = $customer['PrimaryPhone']['FreeFormNumber'];
        $phone = str_replace(['(', ')', '-', ' '], "", $phone);
    } catch (\Exception $e) {
    }

    try {

        $email = $customer['PrimaryEmailAddr']['Address'];
        $email = strtolower($email);
    } catch (\Exception $e) {
    }

    return lookupGHLContact($name, $email, $phone);
}
function getReimburse($id)
{
    $reim = quickbook_api_call('reimbursecharge/' . $id);
    return json_decode(json_encode($reim['ReimburseCharge'] ?? []));
}
function getQBInvoice($id, $paid = false, $ref = '', $balance = 0)
{
    $inv = quickbook_api_call('invoice/' . $id . qbVersion());

    if (!isset($inv['Invoice'])) {
        // if(request()->ajax()){
        //     abort(response()->json(['result' => 'error', 'message'=>'Invoice no longer exists']));
        // }
        response()->json('Invoice no longer exists or please refresh the page')->send();
        die();
        //response()->json('Invoice no longer exists')
    }
    $invoice = $inv['Invoice'];
    $data = ['type' => 'Invoice', 'status' => true, 'message' => '', 'data' => $invoice];
    if ($paid) {

        // if(isset($invoice['LinkedTxn']) && count($invoice['LinkedTxn'])>0){
        //      $data['status']=false;
        //      $data['message']='Already paid';
        // }else{
        $data['type'] = 'Payment';
        if ($balance == 0) {
        }
        $balance = $invoice['Balance'];

        $customerRef = ['value' => $invoice['CustomerRef']['value']];
        $data1 = ['TotalAmt' => $balance, 'CustomerRef' => $customerRef];
        $data1 = json_encode($data1);
        $inv = quickbook_api_call('payment?minorversion=40', 'POST', $data1);
        if (isset($inv['Payment'])) {
            $payment = $inv['Payment'];
            $line_item = [['Amount' => $balance, 'LinkedTxn' => [['TxnId' => $id, 'TxnType' => 'Invoice']]]];
            $data1 = ['TotalAmt' => $balance, 'CustomerRef' => $customerRef, 'SyncToken' => $payment['SyncToken'], 'Id' => $payment['Id'], 'PaymentRefNum' => $ref, 'Line' => $line_item];
            $data1 = json_encode($data1);
            $inv = quickbook_api_call('payment?minorversion=40', 'POST', $data1);
            if (isset($inv['Payment'])) {
                $payment = $inv['Payment'];
            } else {
                $data['status'] = false;
                $data['message'] = 'Eror while updating payment';
                $data['response'] = $inv;
            }
            $data['data'] = $payment;
        } else {
            $data['status'] = false;
            $data['message'] = 'Eror while creating payment';
            $data['response'] = $inv;
        }
        // }

    }
    return $data;
}


function save_qb_tokens($result, $key)
{
    insert_setting('quickbookaccess_token', $result['access_token'] ?? '', $key);
    insert_setting('quickbookrefresh_token', $result['refresh_token'] ?? '', $key);
}

function quickbook_api_call($path = '', $method = 'get', $data = '', $skiprealm = false)
{
    $client = new \App\Helpers\QuickBook();
    $cmp = session('company_id');
    if (auth()->check()) {
        $cmp = auth()->user()->company;
    }
    $loginid = $cmp->key_id ?? $cmp;
    $realmid = get_company_field($loginid, 'quickbookrealmid');
    if ($realmid == '') {
        return [];
    }
    if (!$skiprealm) {
        $path = $realmid . '/' . $path;
    }

    $access_token = get_company_field($loginid, 'quickbookaccess_token');

    $refresh_token = get_company_field($loginid, 'quickbookrefresh_token');
    $call = $client->api_call($path, $access_token, $method, $data);
    ///dd($call);
    try {
        if (is_array($call)) {
            $save_token = false;
            if (isset($call['Fault']) || isset($call['fault'])) {
                $call = $call['Fault'] ?? $call['fault'];
                if (isset($call['type']) && $call['type'] == 'AUTHENTICATION') {
                }
            }

            if ((isset($call['Error']) && is_array($call['Error'])) || (isset($call['error']) && is_array($call['error']))) {

                $error = ($call['Error'] ?? $call['error'][0]) ?? $call['error'];
                $message = $error['Message'] ?? $error['message'];

                if (strpos($message, 'message=AuthenticationFailed') !== false) {


                    $result = $client->refreshAccessToken($refresh_token);
                    save_qb_tokens($result, $loginid);
                    return quickbook_api_call($path, $method, $data);
                }
            }
        }
    } catch (\Exception $e) {
    }
    return $call;
}


?>