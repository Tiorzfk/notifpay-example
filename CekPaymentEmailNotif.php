<?php
    function cekApiNotipaySmsNotif()
    {
        //transaksi yang belum terverifikasi
        //dan filter sesuai bank yang menggunakan email notif
        $donation = Donations::select('id', 'fullname', 'campaigns_id', 'donation', 'no_hp', 'bank_swift_code')
                    ->whereIn('bank_swift_code', ['BRI'])
                    ->where('approved', '0')->get();
        //jika ada
        if(count($donation))
        {
            $foundPayment = [] ;

            //looping transaksi tersebut dan kirim ke api notifpay
            foreach ($donation as $key => $value) {
                $data['id_donation'] = $value['id'];
                $data['nominal'] = $value['donation'];
                $data['nama_donatur'] = $value['fullname'];
                $data['nama_bank'] = 'BNI';
                $donation = $this->callApi("POST" , 'http://localhost:8089/api/checkEmail' , json_encode($data));
                $result = json_decode($donation);

                //jika transaksi ditemukan, tampung data ke array
                if($result->status == 'success')
                {
                    array_push($foundPayment,$value);
                }
            }
        }

        //jika data tampung tidak kosong
        if(count($foundPayment) > 0)
        {
            //looping data tersebut
            foreach ($foundPayment as $k => $v) {

                //ubah transaksi menjadi terverifikasi
                $donate = Donations::findOrFail($v['id']);
                $donate->approved = '1' ;
                $donate->save();

                //kirim sms ke donatur bahwa transaksi telah terverifikasi
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