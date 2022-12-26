<?php

function importCheck($id)
{
    $next_round = User::with('reports')->find($id);

    $data = ['check' => 0, 'next_round' => ''];

    if ($next_round && count($next_round->reports)) {
        $contact_id = $next_round->client_id;
        $company_id = $next_round->company_id;
        $rounds = $next_round->reports;
        $counts = count($rounds);

        if ($counts > 0) {
            // return get_values_by_id('round_days_number',$company_id);
            $lastdate  = $rounds[$counts - 1]->created_at;
            $next_round = Carbon::parse($lastdate)->addDays(setting('import_duration', 1));
            $check = Carbon::now()->diffInDays($next_round);
            $data['next_round'] = $next_round->format('d-m-Y');
            $data['check'] = $check;
        }
    }
    return $data;
}
