<?php
    function cekApiNotipaySmsNotif()
    {
        $donation = Donations::select('id', 'fullname', 'campaigns_id', 'donation', 'no_hp', 'bank_swift_code')
                    ->whereIn('bank_swift_code', ['Mandiri'])
                    ->where('approved', '0')->get();
        if(count($donation))
        {
            $foundPayment = [] ;
            foreach ($donation as $key => $value) {
                $data['id_donation'] = $value['id'];
                $data['nominal'] = $value['donation'];
                $data['nama_donatur'] = $value['fullname'];
                $data['nama_bank'] = 'BNI';
                $donation = $this->callApi("POST" , 'http://localhost:8089/api/reqNotif' , json_encode($data));
                $result = json_decode($donation);

                if($result->status == 'success')
                {
                    array_push($foundPayment,$value);
                }
            }
        }
        if(count($foundPayment) > 0)
        {
                foreach ($foundPayment as $k => $v) {
                    $donate = Donations::findOrFail($v['id']);
                    $donate->approved = '1' ;
                    $donate->save();

                    $campaign = Campaigns::find($v->campaigns_id);
                    
                    $dataMessage['no_hp'] = $v->no_hp;
                    $dataMessage['message'] = 'Dear '.$v->fullname.', donasi anda pada campaign '.$campaign->title.' sebesar Rp. '.$v->donation.' berhasil kami konfirmasi. Terimakasih atas donasi anda.';
                    $this->callApi("POST", 'http://localhost:8089/api/sendMessage' , json_encode($dataMessage));
                }
                return response()->json(['status' => 'approved']);
        }else {
                return response()->json(['status' => 'error'] , 400);
        }
    }

?>