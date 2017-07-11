<?php
defined('BASEPATH') OR exit('No direct script access allowed');
@include_once( APPPATH . 'controllers/Front_Controller.php');
class Customer extends Front_Controller 
{	
	public function __construct() 
  {

		parent::__construct();	
		$this->load->helper(array('url','html','form'));
		$this->load->library('session','form_validation');
		$this->load->library('email');
		$this->load->model('customer_model'); 
			
 }
 

  
  

  public function locationsearch(){
		$post=$this->input->post();
	  	$data['product_search']= $this->customer_model->get_product_search_location($post['locationarea']);
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/productsearch', $data);
		$this->template->render();
	  
  }
  public function account(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['profile_details']= $this->customer_model->get_profile_details($customerdetails['customer_id']);

		$this->template->write_view('content', 'customer/profile', $data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 } 
 public function editprofile(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['locationdata'] = $this->home_model->getlocations();
		$data['profile_details']= $this->customer_model->get_profile_details($customerdetails['customer_id']);

		$this->template->write_view('content', 'customer/editprofile', $data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 }
 public function updateprofilepost(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		
		if($post['email']!=$customerdetails['cust_email']){
			$emailcheck= $this->customer_model->email_unique_check($post['email']);
			if(count($emailcheck)>0){
				$this->session->set_flashdata('errormsg','Email id already exits.please use another Email id');
				redirect('customer/editprofile');
			}
			
		}
		
		
		$cust_upload_file= $this->customer_model->get_profile_details($customerdetails['customer_id']);
		if($_FILES['profile']['name']!=''){
			$profilepic=$_FILES['profile']['name'];
			move_uploaded_file($_FILES['profile']['tmp_name'], "uploads/profile/" . $_FILES['profile']['name']);

			}else{
			$profilepic=$cust_upload_file['cust_propic'];
			}
		$details=array(
		'cust_firstname'=>$post['fname'],
		'cust_lastname'=>$post['lname'],
		'cust_email'=>$post['email'],
		'cust_mobile'=>$post['mobile'],
		'cust_propic'=>$profilepic,
		'address1'=>$post['address1'],
		'address2'=>$post['address2'],
		'area'=>$post['area'],
		);
		//echo '<pre>';print_r($details);exit;
		$updatedetails= $this->customer_model->update_deails($customerdetails['customer_id'],$details);
		if(count($updatedetails)>0){
			$this->session->set_flashdata('success','Profile Successfully updated');
			redirect('customer/account');
		}

		//echo '<pre>';print_r($post);exit;
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 }
 public function addcart(){
	 
	if($this->session->userdata('userdetails'))
	 {
		$post=$this->input->post();
		//echo '<pre>';print_r($post);
		$customerdetails=$this->session->userdata('userdetails');
		$details= $this->customer_model->get_product_details($post['producr_id']);
		
		if($details['offer_percentage']!='' && $details['offer_type']!=4){
			$item_price= ($details['item_cost']-$details['offer_amount']);
			
			$price	=(($post['qty']) * ($item_price));		
		}else{
			$price= (($post['qty']) * ($details['item_cost']));
			$item_price=$details['item_cost'];
		}
		if($details['category_id']==1){
			if($price <500){
				$delivery_charges=35;
			}else{
				$delivery_charges=0;
			}
		}else{
			
			if($price <500){
				$delivery_charges=75;
			}else if(($price > 500) && ($price < 1000)){
				$delivery_charges=35;
			}else if($price >1000){
				$delivery_charges=0;
			}
		}
		//echo '<pre>';print_r($details);exit;
		
		
		$adddata=array(
		'cust_id'=>$customerdetails['customer_id'],
		'item_id'=>$post['producr_id'],
		'qty'=>$post['qty'],
		'item_price'=>$item_price,
		'total_price'=>$price,
		'delivery_amount'=>$delivery_charges,
		'seller_id'=>$details['seller_id'],
		'category_id'=>$details['category_id'],
		'create_at'=>date('Y-m-d H:i:s'),
		);
		//echo '<pre>';print_r($adddata);exit;
		$data['cart_items']= $this->customer_model->get_cart_products($customerdetails['customer_id']);
		
			foreach($data['cart_items'] as $pids) { 
						
							$rid[]=$pids['item_id'];
			}
		if(in_array($post['producr_id'],$rid)){
			
			if(isset($post['category_id']) && $post['category_id']=!'' ){
				$this->session->set_flashdata('adderror','Product already Exits');
				redirect('category/view/'.base64_encode($post['category_id']));
				
			}else if(isset($post['wishlist']) && $post['wishlist']=!'' ){
			$this->session->set_flashdata('adderror','Product already added to the cart');
			redirect('customer/wishlist');	
			}else{
			$this->session->set_flashdata('error','Product already Exits');
			redirect('category/productview/'.base64_encode($post['producr_id']));	
			}
			
		}else{
			$save= $this->customer_model->cart_products_save($adddata);
			if(count($save)>0){
			$this->session->set_flashdata('productsuccess','Product Successfully added to the cart');
			redirect('customer/cart');

			}
		
		}
	
		
	 }else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 }
 public function orders(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['orders_list']= $this->customer_model->order_list($customerdetails['customer_id']);
		
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/orders', $data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 } 
 public function cart(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['cart_items']= $this->customer_model->get_cart_products($customerdetails['customer_id']);
		$data['carttotal_amount']= $this->customer_model->get_cart_total_amount($customerdetails['customer_id']);
		
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/cart', $data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 } 
 public function wishlist(){
	 
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['whistlist_items']= $this->customer_model->get_whishlist_products($customerdetails['customer_id']);
		
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/wishlist', $data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	} 
	 
 } 
 public function updatecart(){
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		
		$details= $this->customer_model->get_product_details($post['product_id']);
		//echo '<pre>';print_r($details);exit;
		
		if($details['offer_percentage']!='' && $details['offer_type']!=4){
			$item_price= ($details['item_cost']-$details['offer_amount']);
			
			$price	=(($post['qty']) * ($item_price));		
		}else{
			$price= (($post['qty']) * ($details['item_cost']));
			$item_price=$details['item_cost'];
		}
		if($details['category_id']==1){
			if($price <500){
				$delivery_charges=35;
			}else{
				$delivery_charges=0;
			}
		}else{
			
			if($price <500){
				$delivery_charges=75;
			}else if(($price > 500) && ($price < 1000)){
				$delivery_charges=35;
			}else if($price >1000){
				$delivery_charges=0;
			}
		}
		//echo "<pre>";print_r($post);exit;
		$updatedata=array(
		'qty'=>$post['qty'],
		'item_price'=>$item_price,
		'total_price'=>$price,
		'delivery_amount'=>$delivery_charges,
		);
		
		$update= $this->customer_model->update_cart_qty($customerdetails['customer_id'],$post['product_id'],$updatedata);
		
		//echo '<pre>';print_r($update);exit;
		if(count($update)>0){
			$this->session->set_flashdata('productsuccess','Product Quantity Successfully Updated!');
			redirect('customer/cart');	
			
		}
		
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
 } 
 public function deletecart(){
	 if($this->session->userdata('userdetails'))
	 {
		$item_id=base64_decode($this->uri->segment(3));
		$id=base64_decode($this->uri->segment(4));
		//echo '<pre>';print_r($item_id);exit; 
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		$delete= $this->customer_model->delete_cart_item($customerdetails['customer_id'],$item_id,$id);
		if(count($delete)>0){
			$this->session->set_flashdata('productsuccess','cart Item Successfully deleted!');
			redirect('customer/cart');	
			
		}
		
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
 }
 public function deletewishlist(){
	 if($this->session->userdata('userdetails'))
	 {
		$whishid=base64_decode($this->uri->segment(3));
		//echo '<pre>';print_r($item_id);exit; 
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		$delete= $this->customer_model->delete_wishlist_item($customerdetails['customer_id'],$whishid);
		if(count($delete)>0){
			$this->session->set_flashdata('productsuccess','Wishlist Item Successfully deleted!');
			redirect('customer/wishlist');	
			
		}
		
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
 }
 public function billing(){
	 
	
	if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['locationdata'] = $this->home_model->getlocations();
		$data['customerdetail']= $this->customer_model->get_profile_details($customerdetails['customer_id']);
		$data['carttotal_amount']= $this->customer_model->get_cart_total_amount($customerdetails['customer_id']);
		
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/billingadrres',$data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
 } 
 public function billingaddresspost(){
	 
	
	if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		$details=array(
		'cust_id'=>$customerdetails['customer_id'],
		'name'=>$post['name'],
		'mobile'=>$post['mobile'],
		'address1'=>$post['address1'],
		'address2'=>$post['address2'],
		'area'=>$post['area'],
		'create-at'=>date('Y-m-d H:i:s'),
		);
		//echo '<pre>';print_r($details);exit;
		$this->session->set_userdata('billingaddress',$details);		
		$this->session->set_flashdata('success','Billing address successfully saved!');
		redirect('customer/orderpayment');
			
		
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
 } 
 public function orderpayment(){
	 if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$data['carttotal_amount']= $this->customer_model->get_cart_total_amount($customerdetails['customer_id']);
		
		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/payment',$data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
	 
 }
 public function ordersuccess(){
	 if($this->session->userdata('userdetails'))
	 {

	$order_id=base64_decode($this->uri->segment(3));
	//echo '<pre>';print_r($order_id);exit;
	  $customerdetails=$this->session->userdata('userdetails');
		$cart_items= $this->customer_model->get_cart_products($customerdetails['customer_id']);
		//echo '<pre>';print_r($cart_items);exit;
		foreach($cart_items as $items){
		$delete= $this->customer_model->after_payment_cart_item($customerdetails['customer_id'],$items['item_id'],$items['id']);
		}
		$data['order_items']= $this->customer_model->get_order_items($order_id);
		$data['carttotal_amount']= $this->customer_model->get_successorder_total_amount($order_id);

		//echo '<pre>';print_r($data);exit;
		$this->template->write_view('content', 'customer/success',$data);
		$this->template->render();
	}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
	}
	 
	 
 }
 public function addwhishlist(){
	 
	
	if($this->session->userdata('userdetails'))
	 {
		$customerdetails=$this->session->userdata('userdetails');
		$post=$this->input->post();
		$detailsa=array(
		'cust_id'=>$customerdetails['customer_id'],
		'item_id'=>$post['item_id'],
		'create_at'=>date('Y-m-d H:i:s'),
		'yes'=>1,
		);
		$whishlist = $this->customer_model->get_whishlist_list($customerdetails['customer_id']);
		if(count($whishlist)>0){
				foreach($whishlist as $lists) { 
							
								$itemsids[]=$lists['item_id'];
				}
			if(in_array($post['item_id'],$itemsids)){
				$removewhislish=$this->customer_model->remove_whishlist($customerdetails['customer_id'],$post['item_id']);
				if(count($removewhislish)>0){
				$data['msg']=2;	
				echo json_encode($data);
				}
			
			}else{
				$addwhishlist = $this->customer_model->add_whishlist($detailsa);
				if(count($addwhishlist)>0){
				$data['msg']=1;	
				echo json_encode($data);
				}
			}
			
		}else{
			$addwhishlist = $this->customer_model->add_whishlist($detailsa);
				if(count($addwhishlist)>0){
				$data['msg']=1;	
				echo json_encode($data);
				}
			
		}
		
	
		
	}else{
		$data['msg']=0;	
		echo json_encode($data); 
	}
	 
 }
 public function index(){
	
	 $test=$this->session->userdata('userdetails');
	 //echo '<pre>';print_r($test);exit;
	 if($this->session->userdata('userdetails'))
	  {
		redirect('');
	 }else{
		$this->load->view( 'customer/register'); 
	 }	

	
 } 
 public function registerpost(){
	
	$post=$this->input->post();
	//echo '<pre>';print_r($post);exit;
	$emailcheck = $this->customer_model->email_check($post['email']);
	if(count($emailcheck)==0){
		$password=md5($post['password']);
		$newpassword=md5($post['confirm_password']);
		if($password == $newpassword ){
			
			$details=array(
			'cust_firstname'=>$post['firstname'],	 	
			'cust_lastname'=>$post['lastname'],
			'cust_email'=>$post['email'],
			'cust_password'=>$password,
			'cust_mobile'=>$post['mobile'],
			'area'=>$this->session->userdata('location_area'),
			);
			$customerdetails = $this->customer_model->save_customer($details);
			
			if(count($customerdetails)>0){
			$getdetails = $this->customer_model->get_customer_details($customerdetails);	
			$this->session->set_userdata('userdetails',$getdetails);
			$this->session->set_flashdata('sucesss',"Successfully registered");
			redirect('');
			}
		}else{
			$this->session->set_flashdata('error',"Password and confirm password do not match");
			redirect('customer');
		}
		
	}else{
		$this->session->set_flashdata('error',"E-mail already exists.Please use another email");
		redirect('customer');	
		
	}


	
 } 
 public function loginpost(){
	 
	$post=$this->input->post();
	//echo '<pre>';print_r($post);exit;
	$pass=md5($post['password']);
	$logindetails = $this->customer_model->login_details($post['email'],$pass);
	//echo '<pre>';print_r($logindetails);
		if(count($logindetails)>0)
		{			
			if($this->session->userdata('location_area')!=''){
			$updatearea = $this->customer_model->update_sear_area($logindetails['customer_id'],$this->session->userdata('location_area'));	
				if(count($updatearea)>0){
					$details = $this->customer_model->get_profile_details($logindetails['customer_id']);
					$this->session->set_userdata('userdetails',$details);
				}
			}else{
				$logindetails = $this->customer_model->login_details($post['email'],$pass);
				$this->session->set_userdata('userdetails',$logindetails);				
			}
			//echo '<pre>';print_r($logindetails);exit;
			$this->session->set_flashdata('sucesss',"Successfully Login");
			redirect('');
		}else{
			$this->session->set_flashdata('loginerror',"Invalid Email Address or Password!");
			redirect('customer');
		}
 }
	public function forgotpassword(){
	  
	$this->load->view( 'customer/forgotpassword'); 
	} 
	

	public function forgotpasswordpost(){
	  
		$post=$this->input->post();
	//echo '<pre>';print_r($post);
	$forgotpass = $this->customer_model->forgot_login($post['emailaddress']);
	//echo '<pre>';print_r($forgotpass);exit;
		if(count($forgotpass)>0)
		{			
			$this->load->library('email');
			$this->email->from('admin@cartinhour.com', 'CartInHour');
			$this->email->to($post['emailaddress']);
			$this->email->subject('CartInHour - Forgot Password');
			$html = "Click this link to reset your password. ".site_url('customer/resetpassword/'.base64_encode($forgotpass['cust_email']).'/'.base64_encode($forgotpass['customer_id']));
			//echo $html;exit;
			$this->email->message($html);
			$this->email->send();
			$this->session->set_flashdata('forsuccess','Check Your Email to reset your password!');
			redirect('customer');
		}else{
			$this->session->set_flashdata('error',"Invalid Email Address!");
			redirect('customer/forgotpassword');
		}
	}

	public function resetpassword(){
	
		$data['cust_id'] = $this->uri->segment(4);
		$data['email']= $this->uri->segment(3);
	$this->load->view( 'customer/resetpassword',$data); 
	} 
	
	
	public function resetpasswordpost(){
	
			$post=$this->input->post();
		//echo '<pre>';print_r($post);exit;
			if(isset($post['newpassword']) && $post['confirmpassword'] !='' )
				{
					if(md5($post['newpassword'])== md5($post['confirmpassword']))
					{
						$users = $this->customer_model->update_password($post['newpassword'],base64_decode($post['cust_id']),base64_decode($post['email']));
						
						//echo '<pre>';print_r($users);exit;
						if(count($users)>0)
						{
						$this->load->library('email');
						$this->email->from('admin@cartinhour.com', 'CartInHour');
						$this->email->to(base64_decode($post['email']));
						$this->email->subject('CartInHour - Forgot Password');
						$html = "Pasword Successfully changed";
						//echo $html;exit;
						$this->email->message($html);
						$this->email->send();
						
							$this->session->set_flashdata("forsuccess","Password successfully changed!");
							redirect('customer');
						}
					}
					else
					{
						$this->session->set_flashdata("error","Passwords are Not matched!");
						redirect('customer/resetpassword/'.$post['email'].'/'.$post['cust_id']);
					}
		}
	}
	public function changepassword(){
	
		
		if($this->session->userdata('userdetails'))
		{
				$this->template->write_view('content', 'customer/changepassword');
				$this->template->render();
		}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
		}
	} 


	



	public function changepasswordpost(){
		if($this->session->userdata('userdetails'))
		{
			$customerdetail=$this->session->userdata('userdetails');
		$changepasword = $this->input->post();
		//echo '<pre>';print_r($changepasword);
		$currentpostpassword=md5($changepasword['oldpassword']);
		$newpassword=md5($changepasword['newpassword']);
		$conpassword=md5($changepasword['confirmpassword']);
		$this->load->model('users_model');
			$userdetails = $this->customer_model->getcustomer_oldpassword($customerdetail['customer_id'],$customerdetail['role_id']);
			//print_r($userdetails);exit;			
			$currentpasswords=$userdetails['cust_password'];
			//print_r($currentpasswords);exit;
			if($currentpostpassword == $currentpasswords ){
				if($newpassword == $conpassword){
						$this->load->model('users_model');
						$passwordchange = $this->customer_model->set_password($customerdetail['customer_id'],$customerdetail['role_id'],$conpassword);
						//echo $this->db->last_query();exit;
						if (count($passwordchange)>0)
							{
								$this->session->set_flashdata('updatpassword',"Password successfully changed!");
								redirect('customer/changepassword');
							}
							else
							{
								$this->session->set_flashdata('passworderror',"Something went wrong in change password process!");
								redirect('customer/changepassword');
							}
				}else{
					$this->session->set_flashdata('passworderror',"New password and confirm password was not matching");
					redirect('customer/changepassword');
				}
			}else{
					$this->session->set_flashdata('passworderror',"Your Old password is incorrect. Please try again.");
					redirect('customer/changepassword');
				}
		}else{
		 $this->session->set_flashdata('loginerror','Please login to continue');
		 redirect('customer');
		}
	}  	
 
	public function logout(){
		
		$userinfo = $this->session->userdata('userdetails');
		//echo '<pre>';print_r($userinfo );exit;
        $this->session->unset_userdata($userinfo);
        $this->session->unset_userdata('location_area');
		$this->session->sess_destroy('userdetails');
		$this->session->unset_userdata('userdetails');
        redirect('');
	}



	/* Inventory managent*/

	public function inve(){
	  //$this->load->view('customer/inventry/header');
	  //$this->load->view('customer/inventry/sidebar');
	  $this->load->view('customer/inventry/login');
	  //$this->load->view('customer/inventry/footer');
	
  }
  public function inve_dashboard(){
  	$data['users'] = $this->session->userdata('userdetails');
  	//echo "<pre>";print_r($data);exit;
	  $this->load->view('customer/inventry/header');
	  $this->load->view('customer/inventry/sidebar');
	  $this->load->view('customer/inventry/index',$data);
	  $this->load->view('customer/inventry/footer');
	
  }

	public function password()
	{
		$data['cust_id'] = base64_decode($this->uri->segment(4));
		//echo "<pre>";print_r($data);exit;
		$data['cust_email']= base64_decode($this->uri->segment(3));
		$this->load->view('customer/setpassword',$data);
	}

	public function set_password()
	{

		
		$pass_post = $this->input->post();	
		//echo "<pre>";print_r($pass_post);exit;	
		$newpassword=md5($pass_post['password']);
		$conpassword=md5($pass_post['confirmpassword']);
		$cust_email = $pass_post['cust_email'];
		$cust_id = $pass_post['cust_id'];
		//$cust_email = $this->input->post('cust_email');
		//echo "<pre>";print_r($cust_email);exit;
		if($newpassword == $conpassword)
		{
			$test  = $this->customer_model->get_user($pass_post['cust_id'],$pass_post['cust_email']);
			//echo "<pre>";print_r($test);exit;
			if($cust_email=$test['cust_email'] && $cust_id=$test['customer_id'])
			{
				$passwordset = $this->customer_model->setpassword_user($pass_post['cust_id'],$conpassword);
				//echo "<pre>";print_r($passwordset);exit;
				if (count($passwordset)>0)
				{
					$this->session->set_flashdata('dashboard',"Welcone To Inventory Management!");
					redirect('customer/inve');
				}
				else
				{
					$this->session->set_flashdata('passworderror',"Something went wrong in set password process!");
					redirect('customer/password/'.base64_encode($pass_post['cust_email']).'/'.base64_encode($pass_post['cust_id']
					));
				}					
			}
			else
			{	
			$this->session->set_flashdata('passworderror',"Your Email Id Is Worng");
				redirect('customer/password/'.base64_encode($pass_post['cust_email']).'/'.base64_encode($pass_post['cust_id']));			
								
			}				
		}
		else
		{
			$this->session->set_flashdata('passworderror',"New password and confirm password was not matching");
			redirect('customer/password/'.base64_encode($pass_post['cust_email']).'/'.base64_encode($pass_post['cust_id']));
		}
		
	}
	
	public function inventery_loginpost(){
	 
	$post=$this->input->post();
	//echo '<pre>';print_r($post);exit;	
	$pass=md5($post['password']);
	$role_id =$this->customer_model->role_ids($post['email']);
	//echo '<pre>';print_r($role_id);exit;
	$logindetails = $this->customer_model->inve_login_details($post['email'],$pass,$role_id['role_id']);
	//echo '<pre>';print_r($logindetails);exit;
		if(count($logindetails)>0)
		{
			$this->session->set_userdata('userdetails',$logindetails);		
			$this->session->set_flashdata('sucesss',"Successfully Login");
			redirect('customer/inve_dashboard');
		}else{
			$this->session->set_flashdata('login_error',"Invalid Email Address or Password!");
			redirect('customer/inve');
		}
 }

 public function inve_changepassword()
	{		
		$this->load->view('customer/inventry/header');
			$this->load->view('customer/inventry/changepassword');
		$this->load->view('customer/inventry/footer');
	  	$this->load->view('customer/inventry/sidebar');
	  
	  
		
	}
	public function inve_changepasswordpost(){
		if($this->session->userdata('userdetails'))
		{
		$customerdetail=$this->session->userdata('userdetails');
		$changepasword = $this->input->post();
		//echo '<pre>';print_r($changepasword);
		$currentpostpassword=md5($changepasword['oldpassword']);
		$newpassword=md5($changepasword['newpassword']);
		$conpassword=md5($changepasword['confirmpassword']);
		$this->load->model('users_model');
			$userdetails = $this->customer_model->getcustomer_oldpassword($customerdetail['customer_id'],$customerdetail['role_id']);
			//print_r($userdetails);exit;			
			$currentpasswords=$userdetails['cust_password'];
			//print_r($currentpasswords);exit;
			if($currentpostpassword == $currentpasswords ){
				if($newpassword == $conpassword){
						$this->load->model('users_model');
						$passwordchange = $this->customer_model->set_password($customerdetail['customer_id'],$customerdetail['role_id'],$conpassword);
						//echo $this->db->last_query();exit;
						if (count($passwordchange)>0)
							{
								$this->session->set_flashdata('updatpassword',"Password successfully changed!");
								redirect('customer/inve_changepassword');
							}
							else
							{
								$this->session->set_flashdata('passworderror',"Something went wrong in change password process!");
								redirect('customer/inve_changepassword');
							}
				}else{
					$this->session->set_flashdata('passworderror',"New password and confirm password was not matching");
					redirect('customer/inve_changepassword');
				}
			}else{
					$this->session->set_flashdata('passworderror',"Your Old password is incorrect. Please try again.");
					redirect('customer/inve_changepassword');
				}
		}else{
		 $this->session->set_flashdata('login_error','Please login to continue');
		 redirect('customer/inve');
		}
	}




 public function inve_forgotpassword(){
	  
	$this->load->view( 'customer/inventry/forgotpassword'); 
	} 
	public function inve_forgotpasswordpost(){
	  
		$post=$this->input->post();
	//echo '<pre>';print_r($post);
	$forgotpass = $this->customer_model->forgot_login($post['emailaddress']);
	//echo '<pre>';print_r($forgotpass);exit;
		if(count($forgotpass)>0)
		{			
			$this->load->library('email');
			$this->email->from('admin@cartinhour.com', 'CartInHour');
			$this->email->to($post['emailaddress']);
			$this->email->subject('CartInHour - Forgot Password');
			$html = "Click this link to reset your password. ".site_url('customer/inve_resetpassword/'.base64_encode($forgotpass['cust_email']).'/'.base64_encode($forgotpass['customer_id']));
			echo $html;exit;
			$this->email->message($html);
			$this->email->send();
			$this->session->set_flashdata('forsuccess','Check Your Email to reset your password!');
			redirect('customer/inve_forgotpasswordpost');
		}else{
			$this->session->set_flashdata('error',"Invalid Email Address!");
			redirect('customer/inve_forgotpassword');
		}
	}
	public function inve_resetpassword(){
	
		$data['cust_id'] = $this->uri->segment(4);
		$data['email']= $this->uri->segment(3);
	$this->load->view( 'customer/resetpassword',$data); 
	} 
	
	
	public function inve_resetpasswordpost(){
	
			$post=$this->input->post();
		//echo '<pre>';print_r($post);exit;
			if(isset($post['newpassword']) && $post['confirmpassword'] !='' )
				{
					if(md5($post['newpassword'])== md5($post['confirmpassword']))
					{
						$users = $this->customer_model->update_password($post['newpassword'],base64_decode($post['cust_id']),base64_decode($post['email']));
						
						//echo '<pre>';print_r($users);exit;
						if(count($users)>0)
						{
						$this->load->library('email');
						$this->email->from('admin@cartinhour.com', 'CartInHour');
						$this->email->to(base64_decode($post['email']));
						$this->email->subject('CartInHour - Forgot Password');
						$html = "Pasword Successfully changed";
						//echo $html;exit;
						$this->email->message($html);
						$this->email->send();
						
							$this->session->set_flashdata("forsuccess","Password successfully changed!");
							redirect('customer/inve');
						}
					}
					else
					{
						$this->session->set_flashdata("error","Passwords are Not matched!");
						redirect('customer/inve_resetpassword/'.$post['email'].'/'.$post['cust_id']);
					}
		}
	}




	public function categories()
	{
		$data['category'] = $this->customer_model->get_seller_categories();
		//echo "<pre>";print_r($data);exit;
		$this->load->view('customer/inventry/header');
	  	$this->load->view('customer/inventry/sidebar');
	  	$this->load->view('customer/inventry/categories',$data);
	  	$this->load->view('customer/inventry/footer');
		
	}

	public function category_wise_sellers()
	{
		$cid = base64_decode($this->uri->segment(3));
		//print_r($data);exit;
		$data['seller_category'] = $this->customer_model->get_seller_names($cid);
		//echo "<pre>";print_r($data);exit;
		$this->load->view('customer/inventry/header');
	  	$this->load->view('customer/inventry/sidebar');
	  	$this->load->view('customer/inventry/category_wise_sellers',$data);
	  	$this->load->view('customer/inventry/footer');
	}

	public function seller_id_database()
	{
		$data['database_id'] = $this->customer_model->get_seller_databaseid();
		//echo "<pre>";print_r($data);exit;
		 $this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/seller_databaseid',$data);
	   	$this->load->view('customer/inventry/footer');
	}


	public function seller_payments()
	{
		$data['seller_payment'] = $this->customer_model->get_seller_payments();
		//echo "<pre>";print_r($data);exit;
		 $this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/seller_payments',$data);
	   	$this->load->view('customer/inventry/footer');
	}

	public function inventory_management()
	{
		$data['inventory_management'] = $this->customer_model->get_inventory_management();
		//echo "<pre>";print_r($data);exit;
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/inventory_management',$data);
	   	$this->load->view('customer/inventry/footer');
	}
	public function catalog_management()
	{
		$data['catalog_management'] = $this->customer_model->get_catalog_management();
		//echo "<pre>";print_r($data);exit;
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/catalog_management',$data);
	   	$this->load->view('customer/inventry/footer');
	}

	public function both()
	{
		$data['both'] = $this->customer_model->get_both();
		//echo "<pre>";print_r($data);exit;
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/both',$data);
	   	$this->load->view('customer/inventry/footer');
	}


	public function home_page_banner()
	{
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/home_page_banner');
	   	$this->load->view('customer/inventry/footer');
	}
	public function top_offers()
	{
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/top_offers');
	   	$this->load->view('customer/inventry/footer');
	}
	public function deals_of_day()
	{
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/deals_of_day');
	   	$this->load->view('customer/inventry/footer');
	}
	public function season_sales()
	{
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/season_sales');
	   	$this->load->view('customer/inventry/footer');
	}
	public function others()
	{
		$this->load->view('customer/inventry/header');
	   	$this->load->view('customer/inventry/sidebar');
	   	$this->load->view('customer/inventry/others');
	   	$this->load->view('customer/inventry/footer');
	}
	
	
	


	public function inve_logout(){
		
		$userinfo = $this->session->userdata('userdetails');
		//echo '<pre>';print_r($userinfo );exit;
        $this->session->unset_userdata($userinfo);
		$this->session->sess_destroy('userdetails');
		$this->session->unset_userdata('userdetails');
        redirect('customer/inve');
	}





}		
?>