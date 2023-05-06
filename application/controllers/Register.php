<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once(APPPATH."libraries/razorpay/razorpay-php/Razorpay.php");

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;


class Register extends CI_Controller {
	/**
	 * This function loads the registration form
	 */

	public function index()
	{
		$this->load->view('registration-form');
	}

	/**
	 * This function creates order and loads the payment methods
	 */
	public function pay()
	{
		$this->load->library('session');
		// print_r($_POST);
		// exit();
		$api = new Api(RAZOR_KEY, RAZOR_SECRET_KEY);
		/**
		 * You can calculate payment amount as per your logic
		 * Always set the amount from backend for security reasons
		 */
		$_SESSION['payable_amount'] = 100;

		$razorpayOrder = $api->order->create(array(
			'receipt'         => rand(),
			'amount'          => $_SESSION['payable_amount'] * 100, // 2000 rupees in paise
			'currency'        => 'INR',
			'payment_capture' => 1 // auto capture
		));


		$amount = $razorpayOrder['amount'];

		$razorpayOrderId = $razorpayOrder['id'];

		$_SESSION['razorpay_order_id'] = $razorpayOrderId;

		$sess_array = array(
				"name"  => $this->input->post('name'),
				"email"  => $this->input->post('email'),
				"password" => $this->input->post('password'),
				"contact" => $this->input->post('contact'),
				"city" => $this->input->post('city'),
		  );
		$this->session->set_userdata($sess_array);
		
// print_r(  );
// exit();
		$data = $this->prepareData($amount,$razorpayOrderId);

		$this->load->view('rezorpay',array('data' => $data));
	}

	/**
	 * This function verifies the payment,after successful payment
	 */
	public function verify()
	{
		$success = true;
		$error = "payment_failed";
		if (empty($_POST['razorpay_payment_id']) === false) {
			$api = new Api(RAZOR_KEY, RAZOR_SECRET_KEY);
		try {
				$attributes = array(
					'razorpay_order_id' => $_SESSION['razorpay_order_id'],
					'razorpay_payment_id' => $_POST['razorpay_payment_id'],
					'razorpay_signature' => $_POST['razorpay_signature']
				);
				$api->utility->verifyPaymentSignature($attributes);
			} catch(SignatureVerificationError $e) {
				$success = false;
				$error = 'Razorpay_Error : ' . $e->getMessage();
			}
		}

		// print_r($success);
		// exit();
		if ($success == true) {
			/**
			 * Call this function from where ever you want
			 * to save save data before of after the payment
			 */
			$this->setRegistrationData();

			redirect(base_url().'register/success');
		}
		else {
			redirect(base_url().'register/paymentFailed');
		}
	}

	/**
	 * This function preprares payment parameters
	 * @param $amount
	 * @param $razorpayOrderId
	 * @return array
	 */
	public function prepareData($amount,$razorpayOrderId)
	{
		$data = array(
			"key" => RAZOR_KEY,
			"amount" => $amount,
			"name" => "Register",
			"description" => "Test Mode",
			"prefill" => array(
				"name"  => $this->input->post('name'),
				"email"  => $this->input->post('email'),
				"password" => $this->input->post('password'),
				"contact" => $this->input->post('contact'),
				"city" => $this->input->post('city'),
			),
			"notes"  => array(
				"address"  => "Hello World",
				"merchant_order_id" => rand(),
			),
			"theme"  => array(
				"color"  => "#F37254"
			),
			"order_id" => $razorpayOrderId,
		);

// echo "<pre>";
// 		print_r($data);
// 		exit();
		return $data;
	}

	/**
	 * This function saves your form data to session,
	 * After successfull payment you can save it to database
	 */
	public function setRegistrationData()
	{
		// echo "<pre>";
		// print_r($this->session->userdata());
		// exit();
		$name = $this->session->userdata('name');
		$email = $this->session->userdata('email');
		$password = md5($this->session->userdata('password'));
		$contact = $this->session->userdata('contact');
		$city = $this->session->userdata('city');
		$amount = $_SESSION['payable_amount'];

		$registrationData = array(
			'order_id' => $_SESSION['razorpay_order_id'],
			'name' => $name,
			'email' => $email,
			'password' => $password ,
			'contact' => $contact,
			'city' => $city,
			'amount' => $amount,
		);
		$this->db->insert('register', $registrationData);

	}

	/**
	 * This is a function called when payment successfull,
	 * and shows the success message
	 */
	public function success()
	{
		$this->load->view('success');
	}
	/**
	 * This is a function called when payment failed,
	 * and shows the error message
	 */
	public function paymentFailed()
	{
		$this->load->view('error');
	}
	
	
}
