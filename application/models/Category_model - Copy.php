<?php
class Category_model extends MY_Model
{
	function __construct() 
	{
		parent::__construct();
		$this->load->database("default");
	}
	
	/* for search data*/
	public function category_searflietrs(){
		
	$this->db->select('fliter_search.category_id,fliter_search.value,')->from('fliter_search');
	$query=$this->db->get()->result_array();
	$this->db->group_by('Ip_address',$this->input->ip_address());
	$this->db->where('Ip_address',$this->input->ip_address());

	$query=$this->db->get()->result_array();
		foreach ($query as $list){
			echo '<pre>';print_r($list);
			
		}
	}
	public function get_all_category_search_products($data){
		$this->db->select('*')->from('products');
		$this->db->where('products.item_status',1);
		$this->db->where_in('seller_id',$data);
		$this->db->where_in('cusine',$data);
		return $this->db->get()->result_array();
		
	}
	public function save_searchdata($data){
		
		$this->db->insert('fliter_search', $data);
		return $insert_id = $this->db->insert_id();
	}
	public function get_all_subcategory_list()
	{
	
	$this->db->select('category.category_id,subcategory_name,subcategories.subcategory_id')->from('fliter_search');
	$this->db->join('category', 'category.category_id =fliter_search.category_id', 'left');
	$this->db->join('subcategories', 'subcategories.category_id = category.category_id', 'left');
	$this->db->group_by('fliter_search.category_id');
	$query=$this->db->get()->result_array();
	foreach($query as $subcategory)
	{
//echo '<pre>';print_r($subcategory);exit;	
	$return = $this->get_all_subcategory($subcategory['category_id']);

	}
	if(!empty($return))
    {
    return $return;
	}
		
	
	}
	
	
	/*------------------*/
	
	
	public function getCategoryData($where = "")
	{
		
		$sql = 
		"
			SELECT * FROM categories
			
		";
		$result = $this->db->query($sql);
		if($result && $result->num_rows()>0)
		{
			return $result->result_array();
		}	
	}
	
	public function getSidebarData()
	{
		$productcnts = $this->getproductIds();		
		$allCat = $this->getParentCategories($productcnts);
		$retData = $this->getChildCategories($allCat,$productcnts);
		return $retData;
	}
	
	public function getParentCategories($productcnts)
	{
		$sql = 
		"
			SELECT cat.category_id, cat.category_name FROM category AS cat		
			
			ORDER BY cat.category_id
		";
		$result = $this->db->query($sql);
		if($result && $result->num_rows()>0)
		{
			$categoryData = $result->result_array();
			$retVal = array();	
			$cnt = 0;
			foreach($categoryData as $category)
			{
				$retVal[$category["category_id"]] = $category;
				$retVal[$category["category_id"]]["prod_cnt"] = isset($productcnts['category'][$category["category_id"]]["cnt"]) ? $productcnts['category'][$category["category_id"]]["cnt"] : 0;
				
				if($retVal[$category["category_id"]]["prod_cnt"] == "0")
				unset($retVal[$category["category_id"]]);
				$cnt++;
			}
		//	echo "<pre>";print_r($retVal);die;
			return $retVal;
		}
	}
	
	public function getChildCategories($allCats,$productcnts)
	{
		$sql = "
			SELECT subcat.category_id,subcat.category_name FROM category AS subcat										
			
			ORDER BY subcat.category_id
		";
	//	echo "<pre>";print_r($productcnts);die;
		$result = $this->db->query($sql);
		if($result && $result->num_rows()>0)
		{
			$subcategories = $result->result_array();
			$retVal = array();
			$cnt = 0;
			foreach($allCats as $categories)
			{
				$retVal[$categories["category_id"]] = $categories;
				$retVal[$categories["category_id"]]["sub_categories"] = array();
				$cntsub = 0;
				foreach($subcategories as $subcats)
				{
					$parents = explode(",",$subcats["parent_category_id"]);
					foreach($parents as $parent)
					{
						if($parent == $categories["category_id"])
						{
							$retVal[$categories["category_id"]]["sub_categories"][$subcats["category_id"]] = $subcats;
							$retVal[$categories["category_id"]]["sub_categories"][$subcats["category_id"]]["prod_cnt"] = isset($productcnts["category"][$parent]["subcategory"][$subcats["category_id"]]) ? $productcnts["category"][$parent]["subcategory"][$subcats["category_id"]] : 0;
							
					//		echo $productcnts["category"][$parent]["subcategory"][$subcats["category_id"]];
							
							if($retVal[$categories["category_id"]]["sub_categories"][$subcats["category_id"]]["prod_cnt"] == 0)
							{
								unset($retVal[$categories["category_id"]]["sub_categories"][$subcats["category_id"]]);
							}
							else
							$cntsub++;							
						}
					}
				}
				$cnt++;
			}			
			return $retVal;
		}
	}
	
	public function getproductIds()
	{
		$sql = "SELECT product_id,category_id,subcategory_id fROM product
			
		";
		$result = $this->db->query($sql);
		if($result && $result->num_rows()>0)
		{
			$retData = array();
			$retData["category"] = array();			
			$products = $result->result_array();			
			foreach($products as $prods)
			{
				$cats = explode(",",$prods['category_id']);
				foreach($cats as $each)
				{
					if(isset($retData["category"][$each]["cnt"]))
					{
						$retData["category"][$each]["cnt"]++;
					}
					else 
					{
						$retData["category"][$each]["cnt"] = 1;
					}
					$subcats = explode(",",$prods['subcategory_id']);
					foreach($subcats as $eachsub)
					{
						if(isset($retData["category"][$each]["subcategory"][$eachsub]))$retData["category"][$each]["subcategory"][$eachsub]++;
						else $retData["category"][$each]["subcategory"][$eachsub] = 1;
					}
				}				
			}
			return $retData;
			
		}
	}
/* added by vasudevareddy */
	
	
	/* cusine list*/
	public function get_product_details($id)
	{
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('item_id',$id);
		return $this->db->get()->row_array();
	}
	public function delete_privous_searchdata($id)
	{
		$sql1="DELETE FROM fliter_search WHERE id = '".$id."'";
		return $this->db->query($sql1);
	}
	public function get_all_previous_search_fields()
	{
		$this->db->select('*')->from('fliter_search');
		$this->db->where('Ip_address',$this->input->ip_address());
		return $this->db->get()->result_array();
	}
		
	public function get_search_all_subcategory_products()
	{
	
	$this->db->select('fliter_search.*')->from('fliter_search');
	$this->db->group_by('fliter_search.cusine');
	$this->db->group_by('fliter_search.restraent');
	$this->db->group_by('fliter_search.mini_amount');
	$this->db->group_by('fliter_search.max_amount');
	$this->db->group_by('fliter_search.offers');
	$this->db->group_by('fliter_search.brand');
	$this->db->group_by('fliter_search.discount');
	$this->db->group_by('fliter_search.status');
	$this->db->group_by('fliter_search.size');
	$this->db->group_by('fliter_search.color');
	$query=$this->db->get()->result_array();
		foreach ($query as $sorting){
			//echo '<pre>';print_r($sorting);exit;
			if($sorting['cusine']!=''){
				$return['cusine'] = $this->get_cusine($sorting['cusine'],$sorting['category_id']);
			}
			if($sorting['restraent']!=''){
				$return['restraent'] = $this->get_restraent($sorting['restraent'],$sorting['category_id']);
			}
			if($sorting['offers']!=''){
			$return['offers'] = $this->get_offers($sorting['offers'],$sorting['category_id']);
			}
			if($sorting['brand']!=''){
			$return['brand'] = $this->get_brands($sorting['brand'],$sorting['category_id']);
			}
			if($sorting['discount']!=''){
			$return['discount'] = $this->get_discounts($sorting['discount'],$sorting['category_id']);
			}
			if($sorting['color']!=''){
				$return['color'] = $this->get_colors($sorting['color'],$sorting['category_id']);
			}
			if($sorting['size']!=''){
			$return['size'] = $this->get_sizes($sorting['size'],$sorting['category_id']);
			}
			$return['mini_amount'] = $this->get_amount($sorting['mini_amount'],$sorting['max_amount'],$sorting['category_id']);
			$return['status'] = $this->get_status($sorting['status'],$sorting['category_id']);
			
		}
		if(!empty($return))
		{
		return $return;
		}
		
	}
	public function get_offers($offer,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('offers',$offer);
		$this->db->where('category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_brands($brand,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('brand',$brand);
		$this->db->where('category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_discounts($discount,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('discount',$discount);
		$this->db->where('category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_sizes($size,$cid){
		
		$this->db->select('products.*')->from('product_size_list');
		$this->db->join('products', 'products.item_id = product_size_list.item_id', 'left'); //
		$this->db->where('products.item_status',1);
		$this->db->where('product_size_list.p_size_name',$size);
		$this->db->where('products.category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_colors($color,$cid){
		
		$this->db->select('products.*')->from('product_color_list');
		$this->db->join('products', 'products.item_id = product_color_list.item_id', 'left'); //
		$this->db->where('products.item_status',1);
		$this->db->where('product_color_list.color_name',$color);
		$this->db->where('products.category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_status($status,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',$status);
		$this->db->where('item_status',1);
		$this->db->where('category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	public function get_cusine($cusine,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('cusine',$cusine);
		$this->db->where('category_id',$cid);
		
		return $this->db->get()->result_array();
		//echo '<pre>';print_r($qq);exit;
		
	}
	public function get_restraent($restraent,$cid){
		
		$this->db->select('*')->from('products');
		$this->db->where('item_status',1);
		$this->db->where('seller_id',$restraent);
		$this->db->where('category_id',$cid);
		return $this->db->get()->result_array();
		
	}
	
	/* food categorywise*/
	public function get_amount($min_amunt,$max_amount,$cid){
		
		$sql = "SELECT * FROM products WHERE category_id ='".$cid."' AND item_status ='1' AND  item_cost BETWEEN '".$min_amunt."' AND '".$max_amount."'";
        return $this->db->query($sql)->result_array();
		//echo $this->db->last_query();exit;
	}

	public function get_all_cusine_list($catid)
	{
	
		$this->db->select('products.cusine')->from('products');
		$this->db->where('category_id',$catid);
		$this->db->where('item_status',1);
		$this->db->where('cusine!=','');
		$this->db->group_by('cusine');
		return $this->db->get()->result_array();
		
	}
	public function get_all_myrestaurant_list($catid)
	{
		$this->db->select('sellers.seller_name,sellers.seller_id')->from('products');
		$this->db->join('sellers', 'sellers.seller_id = products.seller_id', 'left');	
		$this->db->where('products.category_id',$catid);
		$this->db->where('products.item_status',1);
		$this->db->where('products.seller_id!=','');
		$this->db->group_by('products.seller_id');
		return $this->db->get()->result_array();
	}
	
	/* food categorywise*/
	/*electronic category*/
	public function get_all_color_list($catid){
		$this->db->select('product_color_list.color_name')->from('products');
		$this->db->join('product_color_list', 'product_color_list.item_id = products.item_id', 'left');	
		$this->db->where('products.category_id',$catid);
		$this->db->where('products.item_status',1);
		$this->db->where('product_color_list.color_name!=','');
		$this->db->group_by('product_color_list.color_name');
		return $this->db->get()->result_array();
	}
	public function get_all_size_list($catid){
		$this->db->select('product_size_list.p_size_name')->from('products');
		$this->db->join('product_size_list', 'product_size_list.item_id = products.item_id', 'left');	
		$this->db->where('products.category_id',$catid);
		$this->db->where('products.item_status',1);
		$this->db->where('product_size_list.p_size_name!=','');
		$this->db->group_by('product_size_list.p_size_name');
		return $this->db->get()->result_array();
	}
	/*electronic category*/
	/* GROCERY categorywise*/
	public function get_all_brand_list($catid){
		
		$this->db->select('products.brand')->from('products');
		$this->db->where('category_id',$catid);
		$this->db->where('item_status',1);
		$this->db->where('brand!=','');
		$this->db->group_by('brand');
		return $this->db->get()->result_array();
		
	}

	public function get_all_price_list($catid)
	{
		$this->db->select('products.item_cost')->from('products');
		$this->db->where('category_id',$catid);
		$this->db->where('item_status',1);
		$this->db->where('item_cost!=','');
		$this->db->group_by('item_cost');
		return $this->db->get()->result_array();
	}
	public function get_all_discount_list($catid)
	{
		$this->db->select('products.discount')->from('products');
		$this->db->where('category_id',$catid);
		$this->db->where('item_status',1);
		$this->db->where('discount!=','');
		$this->db->group_by('discount');
		return $this->db->get()->result_array();
	}
	public function get_all_avalibility_list($catid)
	{
		$this->db->select('products.item_status')->from('products');
		$this->db->where('category_id',$catid);
		$this->db->where('item_status',1);
		$this->db->where('item_quantity!=',0);
		return $this->db->get()->row_array();
	}
	public function get_all_offer_list($catid)
	{
		$this->db->select('products.offers')->from('products');
		$this->db->where('products.category_id',$catid);
		$this->db->where('products.item_status',1);
		$this->db->where('products.offers!=','');
		$this->db->group_by('products.offers');
		return $this->db->get()->result_array();
	}
	
	/* GROCERY categorywise*/
	
	public function get_category_name($catid)
	{
	
	$this->db->select('category.category_name')->from('category');
    $this->db->where_in('category_id', $catid);
	return $this->db->get()->row_array();
		
	}
	public function get_all_subcategory_products($category_ids)
	{
	
	$this->db->select('products.*')->from('products');
	$this->db->join('subcategories', 'subcategories.subcategory_id = products.subcategory_id', 'left');	
	$this->db->join('category', 'category.category_id =products.category_id', 'left');	
    $this->db->where_in('products.category_id', $category_ids);
    $this->db->where('products.item_status', 1);
	return $this->db->get()->result_array();
		
	}
	public function get_all_subcategory_product($category_ids)
	{
	
	$this->db->select('products.*')->from('products');
	$this->db->join('subcategories', 'subcategories.subcategory_id = products.subcategory_id', 'left');	
	$this->db->join('category', 'category.category_id =products.category_id', 'left');	
    $this->db->where('products.category_id', $category_ids);
    $this->db->where('products.item_status', 1);
	return $this->db->get()->result_array();
		
	}
	public function get_all_subcategory_products_list($subcatid)
	{
	
	$this->db->select('products.*')->from('products');
	$this->db->join('subcategories', 'subcategories.subcategory_id = products.subcategory_id', 'left');	
	$this->db->join('category', 'category.category_id =products.category_id', 'left');	
    $this->db->where('subcategories.subcategory_id', $subcatid);
    $this->db->where('products.item_status', 1);
	return $this->db->get()->result_array();
		
	}
	public function get_all_subcategorys($category_ids)
	{
	
	$this->db->select('category.category_id,subcategories.subcategory_name,subcategories.subcategory_id')->from('products');
	$this->db->join('subcategories', 'subcategories.subcategory_id = products.subcategory_id', 'left');	
	$this->db->join('category', 'category.category_id =products.category_id', 'left');
	$this->db->group_by('subcategories.subcategory_id');
	$this->db->order_by('subcategories.subcategory_id', 'ASC'); 
	$this->db->where('subcategories.status', 1);	
	$this->db->where_in('subcategories.category_id', $category_ids);	
	return $this->db->get()->result_array();
		
	
	}
	public function get_all_subcategory($category_id)
	{
	
	$this->db->select('category.category_id,subcategories.subcategory_name,subcategories.subcategory_id')->from('products');
	$this->db->join('subcategories', 'subcategories.subcategory_id = products.subcategory_id', 'left');	
	$this->db->join('category', 'category.category_id =products.category_id', 'left');
	$this->db->group_by('subcategories.subcategory_id');
	$this->db->order_by('subcategories.subcategory_id', 'ASC'); 
	$this->db->where('subcategories.status', 1);	
	$this->db->where('subcategories.category_id', $category_id);	
	return $this->db->get()->result_array();
		
	
	}
	
	public function get_all_products($catid){
		
		
		//echo "<pre>";print_r($catid);exit;
		$this->db->select('products.*,category.category_name,item_wishlist.yes')->from('products');
		$this->db->join('category', 'category.category_id = products.category_id', 'left');
		//$this->db->join('reviews', 'reviews.item_id = products.item_id', 'left'); //		//
		$this->db->join('item_wishlist', 'item_wishlist.item_id = products.item_id', 'left'); //		//
		$this->db->where('products.subcategory_id',$catid);
		$this->db->where('products.admin_status',0);
		return $this->db->get()->result_array();
	}
	public function get_list_products($catid){
		
		
		//echo "<pre>";print_r($catid);exit;
		$this->db->select('products.*,category.category_name,item_wishlist.yes')->from('products');
		$this->db->join('category', 'category.category_id = products.category_id', 'left');
		//$this->db->join('reviews', 'reviews.item_id = products.item_id', 'left'); //		//
		$this->db->join('item_wishlist', 'item_wishlist.item_id = products.item_id', 'left'); //		//
		$this->db->where('products.category_id',$catid);
		$this->db->where('products.admin_status',0);
		return $this->db->get()->result_array();
	}
	public function get_list_categories($catid){
		
		$this->db->select('subcategories.*')->from('subcategories');
		$this->db->where('category_id',$catid);
		//$this->db->where('status',1);
		return $this->db->get()->result_array();
	}
	public function get_product_stock($catid){
		
		$this->db->select('products.*')->from('products');
		$this->db->where('subcategory_id',$catid);
		$this->db->where('admin_status',0);
		$this->db->where('item_status',1);
		return $this->db->get()->result_array();
	}
	public function get_category($catid){
		
		$this->db->select('category.category_name')->from('subcategories');
		$this->db->join('category', 'category.category_id = subcategories.category_id', 'left'); //
		$this->db->where('subcategories.subcategory_id',$catid);
		return $this->db->get()->row_array();
	}
	public function get_products_reviews($pid){
		
		$this->db->select('*')->from('reviews');
		$this->db->where('item_id',$pid);
		return $this->db->get()->result_array();
	}
	public function get_products($pid){
		
		$this->db->select('products.*,item_wishlist.yes')->from('products');
		$this->db->join('item_wishlist', 'item_wishlist.item_id = products.item_id', 'left'); //
		$this->db->where('products.item_id',$pid);
		$this->db->where('products.admin_status',0);
		return $this->db->get()->row_array();
	}
	public function save_review($data){
		
		$this->db->insert('reviews', $data);
		return $insert_id = $this->db->insert_id();
	}


		
	
	public function getsubitemdata($cat_id)
 {
  
  	$this->db->select('*')->from('products');
 	$this->db->where('products.category_id',$cat_id);
  	return $query=$this->db->get()->result();  
 }
 // public function getsubitem_one($category_ld)
 // {
  
 //  	$this->db->select('products.item_name')->from('products');
 // 	$this->db->where('products.category_id',$category_ld);
 //  	return $query=$this->db->get()->result();  
 // }


 public function getcompare_item_details($itemid)
 {
  	$this->db->select('*')->from('products');
	$this->db->where('products.item_id',$itemid);
	return $this->db->get()->row_array();
 }
	
}
?>