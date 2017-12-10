<?php

namespace App\Repositories;

use App\User;
use Illuminate\Support\Str;
use DB;

class UserRepository extends BaseRepository
{
    public function __construct(User $user) {
        $this->model = $user;
    }

    public function checkEmailIsExist($email) {
		$token = "";
		$empty_token = "";
        $data = [];
        $query = $this->model
                    ->where('email', $email);
        if($query->count() > 0) {
            $data = $query->get()->toArray();
			
            if(!empty($data)) {
				if(!empty($data[0]['api_token']))
				{
					$token = $data[0]['api_token'];
					return $token;
				}
				else
				{
					$key = '';
					$keys = array_merge(range(0, 9), range('a', 'z'));
					$length =50;
					for ($i = 0; $i < $length; $i++) {
						$key .= $keys[array_rand($keys)];
					}

					$to_be_token = $key;
					$query_update = $this->model
						->where('email', $email)->update(['api_token' => $to_be_token]);
						setcookie('api_token', $to_be_token,  time()+86400); // 1 day cookie
						return $to_be_token;
				}
				
            }
			
        }
		return $token;
        //throw new \Exception('Email id not exist, please provide a correct information', 400);
    }
	
	public function insertingUser($data) {
		$key = Str::random(55);	
		\Log::info(" register post data " . print_r($data, true));
		try {
			$userObj = new $this->model;
			DB::beginTransaction();
				$userObj->name = $data['uname'];
				$userObj->email = $data['email'];
				$userObj->password = bcrypt($data['pwd']);
				$userObj->api_token = $key;
				$userObj->save();
			DB::commit();
		} catch (\Exception $e) {
			DB::rollback();
		 \Log::write('error', 'Import ERROR >> ' . $e->getMessage());
		
			throw new \Exception($e->getMessage());
		}
	// unset($userObj);
		return $key;
	}

}
