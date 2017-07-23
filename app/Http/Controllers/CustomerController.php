<?php

namespace App\Http\Controllers;
// Require autoload generated by Composer
require('/opt/lampp/htdocs/Taskoo/vendor/autoload.php');

use Illuminate\Http\Request;
use App\Customer;
use App\Worker;
use App\WorkerProfile;
use App\SecondarySkills;
use Auth;
use Carbon\Carbon;
use Borla\Chikka\Chikka;

class CustomerController extends Controller
{

	function get_gravatar( $email, $s = 256, $d = 'identicon', $r = 'g', $img = false, $atts = array() ) {
		$url = 'https://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		if ( $img ) {
			$url = '<img src="' . $url . '"';
			foreach ( $atts as $key => $val )
				$url .= ' ' . $key . '="' . $val . '"';
			$url .= ' />';
		}
		return $url;
	}

	protected function getToken()
	{
		return hash_hmac('sha256', str_random(40), config('app.key'));
	}

	public function postRegister(Request $request)
	{
		$this->validate($request, [
		                'email' => 'required|unique:customer_acc|max:255',
		                'contact_number' => 'required|min:9',
		                'password' => 'required|min:8|confirmed',
		                ]);

		$email = $request['email'];
		$fname = $request['first_name'];
		$lname = $request['last_name'];
		$contact = $request['contact_number'];
		$password = bcrypt($request['password']);
		$gravatar = $this->get_gravatar( $email, $s = 256, $d = 'identicon', $r = 'g', $img = false, $atts = array());

		$customer = new Customer;

		$customer->first_name = $fname;
		$customer->last_name = $lname;
		$customer->email = $email;
		$customer->mobile_number = '+639'.$contact;
		$customer->password = $password;
		$customer->gravatar = $gravatar;
		$customer->created_at = Carbon::now();
		$customer->updated_at = Carbon::now();
		$customer->remember_token = $this->getToken();

		$customer->save();

		if (Auth::guard('customers')->attempt(['email' => $request['email'], 'password' => $request['password']])) {
			return view('responses.success_register')->with('user', 'customer');
		}else{
			return redirect()->back()->with('invalid','Invalid Login Credentials.');
		}

	}

	public function login(Request $request)
	{
		$this->validate($request, [
		                'email' => 'required|max:255',
		                'passsword' => 'required|min:8',
		                ]);

		if (Auth::guard('customers')->attempt(['email' => $request['email'], 'password' => $request['passsword']])) {
			return redirect('customer/dashboard');
		}else{
			return redirect()->back()->with('invalid','Invalid Login Credentials.');
		}

		return redirect()->back();
	}

	public function logout()
	{
		Auth::guard('customers')->logout();
		return redirect('/')->header('Clear-Site-Data','cache','storage','executionContexts');
	}

	public function getDashboard()
	{
		return view('customer.dashboard');
	}

	public function getWorkers($type)
	{
		$workers =WorkerProfile::where('primary_skill', $type)->get();

		$data = array ();
		foreach ($workers as $key => $profile) {

			$item = new \stdClass;
			$item->worker = $profile->worker;
			$item->profile = $profile;
			$item->location = $profile->location;
			// $item->save();

			array_push($data, $item);
		}

		return $data;
		// die(print_r($workers));
	}

	public function notifyWorker($id)
	{
		$worker = Worker::find($id);

		echo $worker;
	}

	public function sendSMS(){
		// Set configuration
		$config = [
		    // Shortcode to use
		'shortcode'=> '29290 4109',
  		  // Client ID
		'client_id'=> 'f50e9239d5953c379756085ed03f49fe42e0aadd8bfb0a04d44b25bf6c7ffba8',
 		   // Secret key
		'secret_key'=> '5f9d8b8691dd348746d7ade58e698155046f08df5cde281b2b62bc9621513e0a',
		];

		// Create Chikka object
		$chikka = new Chikka($config);

		$worker = Worker::find(2);
		$customer = Customer::where('id', Auth::guard('customers')->user()->id)->first();

		// die(print_r($worker));
		$messageBody = "Hi ". $worker->first_name. "! This is ". $customer->first_name.". I saw your profile at Taskoo and picked you as the person that will do a job for me. Please reply ASAP at ". $customer->mobile_number."  for more details. Thank you!";

		// die(print($messageBody));
		// Mobile number of receiver and message to send
		$mobile = $worker->mobile_number;
		$message = $messageBody;

		// Send SMS
		$chikka->send($mobile, $message);
	}
}
