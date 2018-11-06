<?php
require "vendor/autoload.php";

Class apiController Extends baseController {
    public function index() {
        
    }

    public function login() {
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $username = $obj['username'];
        $password = $obj['password'];
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($username != "" && $password != "") {
            $user = $this->model->get('userModel');
                        
            $row = $user->getUserByUsername(addslashes($username));
            if ($row) {
                if ($row->password == md5($password) && $row->user_lock != 1) {
                    $result = array(
                        'data' => $row,
                        'msg' => 'Đăng nhập thành công',
                        'err' => 1
                    );
                }
                else{
                    $result = array(
                        'data' => null,
                        'msg' => 'Sai mật khẩu',
                        'err' => 0
                    );
                }
            }
            else{
                $result = array(
                    'data' => null,
                    'msg' => 'Không tồn tại username',
                    'err' => 0
                );
            }
        }
        else{
            $result = array(
                'data' => null,
                'msg' => 'Vui lòng nhập vào username / password',
                'err' => 0
            );
        }

        echo json_encode($result);
    }
    public function approve(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];
        $value = $obj['value'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');
            $customer_model = $this->model->get('customerModel');

            $order_tire = $order_tire_model->getTire($id);

            if ($value>0) {
                $data = array(
                    'approve'=>null,
                    'sale_lock'=>0,
                );

                $url = $customers->customer_agent_link.'/ordertire/approveorderremove';
            }
            else{
                $data = array(
                    'approve'=>$user,
                    'sale_lock'=>1,
                );

                $url = $customers->customer_agent_link.'/ordertire/approveorder';
            }
            

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            if ($order_tire->id_order_agent>0) {

                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $res = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|approve|".$id."|order_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function unlock(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];
        $value = $obj['value'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');

            $data = array(
                'sale_lock'=>$value>0?null:1,
            );
            
            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."lock"."|".$id."|ordertire|".$value."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function addordernumber(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];
        $order_number = strtolower(trim($obj['value']));

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');


            $data = array(
                'order_number'=>$order_number,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $payable_model = $this->model->get('payableModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            
            $data_sale = array(
                'code' => $order_number,
            );
            $tire_sale_model->updateTire($data_sale,array('order_tire'=>$id));
            
            $receivable_data = array(
                'code' => $order_number,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $payable_data = array(
                'code' => $order_number,
            );

            $payable_model->updateCosts($payable_data,array('order_tire'=>$id));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|code|".$id."|order_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function addorder(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $user = $obj['user'];
        $type = $obj['type'];
        $customer = trim($obj['customer']);
        $customer_name = trim($obj['customer_name']);
        $company = trim($obj['company']);
        $mst = trim($obj['mst']);
        $address = trim($obj['address']);
        $phone = trim($obj['phone']);
        $email = trim($obj['email']);
        $contact = trim($obj['contact']);
        $staff = $obj['staff'];
        $brand = $obj['brand'];
        $size = $obj['size'];
        $pattern = $obj['pattern'];
        $number = $obj['number'];
        $maxNumber = $obj['maxNumber'];
        $price = $obj['price'];
        $priceVAT = $obj['priceVAT'];
        $checkPriceVat = $obj['checkPriceVat']==true?1:0;
        $vatPercent = str_replace('.','',$obj['vatPercent']);
        $vatPercent = str_replace(',','.',$vatPercent);
        $vat = str_replace('.','',$obj['vat']);
        $vat = str_replace(',','.',$vat);
        $discount = str_replace('.','',$obj['discount']);
        $discount = str_replace(',','.',$discount);
        $warrantyPercent = str_replace('.','',$obj['warrantyPercent']);
        $warrantyPercent = str_replace(',','.',$warrantyPercent);
        $warranty = str_replace('.','',$obj['warranty']);
        $warranty = str_replace(',','.',$warranty);
        $deliveryDate = str_replace('/', '-', $obj['deliveryDate']);
        $dueDate = str_replace('/', '-', $obj['dueDate']);
        $comment = trim($obj['comment']);
        $total = str_replace('.','',$obj['total']);
        $total = str_replace(',','.',$total);

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($user>0) {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $customer_model = $this->model->get('customerModel');
            $contact_person_model = $this->model->get('contactpersonModel');
            $customer_tire_model = $this->model->get('customertireModel');

            if ($customer == "") {
                if ($customer_name != "") {
                    $cus = $customer_model->getCustomerByWhere(array('customer_name' => $customer_name));
                    if (!$cus) {
                        if ($mst != "") {
                            $cus = $customer_model->getCustomerByWhere(array('mst' => $mst));
                        }
                        if (!$cus) {
                            if ($phone != "") {
                                $cus = $customer_model->getCustomerByWhere(array('customer_phone' => $phone));
                                if (!$cus) {
                                    $cus = $customer_model->getCustomerByWhere(array('REPLACE(customer_phone, " ", "")' => $phone));
                                }
                            }
                        }
                    }
                    

                    if ($cus) {
                        $id_customer = $cus->customer_id;
                    }
                    else{
                        $data_cus = array(
                            'customer_name' => addslashes($customer_name),
                            'company_name' => addslashes($company),
                            'mst' => $mst,
                            'customer_address' => addslashes($address),
                            'customer_phone' => $phone,
                            'customer_email' => $email,
                            'customer_contact' => $contact,
                            'customer_create_user' => $staff,
                            'customer_tire_type' => $type,
                        );

                        $customer_model->createCustomer($data_cus);
                        $id_customer = $customer_model->getLastCustomer()->customer_id;

                        $data_contact_person = array(
                            'contact_person_name' => $contact,
                            'contact_person_phone' => $phone,
                            'contact_person_mobile' => $phone,
                            'contact_person_email' => $email,
                            'contact_person_birthday' => null,
                            'contact_person_address' => null,
                            'contact_person_position' => null,
                            'contact_person_department' => null,
                            'customer' => $id_customer,
                        );
                        $contact_person_model->createCustomer($data_contact_person);
                    }
                    
                }
            }
            else{
                $id_customer = $customer;
                if ($customer_model->getCustomerByWhere(array('customer_id'=>$id_customer,'customer_create_user'=>$staff))) {
                    $data_cus = array(
                        'customer_name' => addslashes($customer_name),
                        'company_name' => addslashes($company),
                        'mst' => $mst,
                        'customer_address' => addslashes($address),
                        'customer_phone' => $phone,
                        'customer_email' => $email,
                        'customer_contact' => $contact,
                        'customer_tire_type' => $type,
                    );

                    $customer_model->updateCustomer($data_cus,array('customer_id'=>$id_customer));
                }
                
            }

            $data = array(
                'customer_type' => $type,
                'order_tire_date' => strtotime(date('d-m-Y')),
                'sale' => $staff,
                'sale_cskh' => $user,
                'customer' => $id_customer,
                'payment' => 1,
                'debt' => null,
                'debt_number_day' => null,
                'deposit' => null,
                'deposit_date' => null,
                'debt_1' => null,
                'debt_1_date' => null,
                'debt_2' => null,
                'debt_2_date' => null,
                'debt_3' => null,
                'debt_3_date' => null,
                'ck_ttn' => null,
                'ck_kho' => null,
                'ck_sl' => null,
                'discount' => $discount,
                'reduce' => null,
                'vat_percent' => $vatPercent,
                'vat' => $vat,
                'warranty_percent' => $warrantyPercent,
                'warranty' => $warranty,
                'delivery_date' => strtotime($deliveryDate),
                'due_date' => strtotime($dueDate),
                'total' => $total,
                'order_tire_number' => 0,
                'order_tire_status' => 0,
                'check_price_vat' => $checkPriceVat,
            );

            $order_tire_model->createTire($data);
            $id_order_tire = $order_tire_model->getLastTire()->order_tire_id;

            $order_tire_number = 0;

            for ($i=0; $i < count($number); $i++) { 
                $data_order = array(
                    'tire_brand' => $brand[$i],
                    'tire_size' => $size[$i],
                    'tire_pattern' => $pattern[$i],
                    'tire_number' => $maxNumber[$i] >= $number[$i] ? $number[$i] : $maxNumber[$i],
                    'tire_price' => str_replace('.', '', $priceVAT[$i]),
                    'tire_price_vat' => str_replace('.', '', $price[$i]),
                    'order_tire' => $id_order_tire,
                    'tire_date' => null,
                );

                $orders = $order_tire_list_model->getTireByWhere(array('tire_brand'=>$data_order['tire_brand'],'tire_size'=>$data_order['tire_size'],'tire_pattern'=>$data_order['tire_pattern'],'order_tire'=>$data_order['order_tire']));
                if ($orders) {
                    $order_tire_list_model->updateTire($data_order,array('order_tire_list_id'=>$orders->order_tire_list_id));
                    $order_tire_number = $order_tire_number - $orders->tire_number + $data_order['tire_number'];
                }
                else{
                    $order_tire_list_model->createTire($data_order);
                    $order_tire_number += $data_order['tire_number'];
                }
            }

            $order_tire_model->updateTire(array('order_tire_number'=>$order_tire_number),array('order_tire_id'=>$id_order_tire));

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."add"."|".$id_order_tire."|order_tire|".implode("-",$data)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data'=>null,
                'msg'=>'Thêm thành công',
                'err'=>1
            );
        }
        

        echo json_encode($result);
    }
    public function editorder(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $order = $obj['order'];
        $user = $obj['user'];
        $orderlist = $obj['orderlist'];
        $brand = $obj['brand'];
        $size = $obj['size'];
        $pattern = $obj['pattern'];
        $number = $obj['number'];
        $price = str_replace('.','',$obj['price']);
        $price = str_replace(',','.',$price);

        if ($order>0 && $brand>0 && $size>0 && $pattern>0 && $number>0) {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');

            $order_tire = $order_tire_model->getTire($order);

            $data = array(
                'order_tire'=>$order,
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
                'tire_price'=>$price,
                'tire_price_vat'=>$price,
            );

            if ($order_tire->check_price_vat == 1) {
                $p = $price;
                $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                $n = $p-$v;

                $data['tire_price'] = $n;
                $data['tire_price_vat'] = $p;
            }
            

            if ($orderlist>0) {
                if (!$order_tire_list_model->getAllTireByWhere($orderlist.' AND order_tire='.$order.' AND tire_brand='.$brand.' AND tire_size='.$size.' AND tire_pattern='.$pattern)) {
                    $order_tire_list = $order_tire_list_model->getTire($orderlist);

                    $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                    $order_tire_list_model->updateTire($data,array('order_tire_list_id'=>$orderlist));

                    $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                    $total_number = 0;
                    $total = 0;
                    $vat = 0;
                    foreach ($order_lists as $od) {
                        $total_number += $od->tire_number;
                        
                        if ($order_tire->check_price_vat == 1) {
                            $p = $od->tire_price_vat;
                            $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                            $n = $p-$v;

                            $vat += $v*$od->tire_number;
                            $total += $od->tire_number*$od->tire_price_vat;
                        }
                        else{
                            $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                        }
                    }

                    $discount = $order_tire->discount+$order_tire->reduce;
                    $warranty = round($total*$order_tire->warranty/100);
                    $total = $total - $discount - $warranty;


                    $data_order = array(
                        'total'=>$total,
                        'order_tire_number'=>$total_number,
                        'vat'=> $vat,
                        'warranty'=>$warranty,
                    );


                    $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                    if($order_tire->order_tire_status==1){
                        $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                        $order_tire_list_old = $order_tire_list_model->getTire($orderlist);

                        $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                        $tire_sale = $tire_sale_model->getTireByWhere(array('tire_brand'=>$order_tire_list->tire_brand,'tire_size'=>$order_tire_list->tire_size,'tire_pattern'=>$order_tire_list->tire_pattern,'order_tire'=>$order_tire_list->order_tire));
                        $data_sale = array(
                            'tire_brand'=>$order_tire_list_old->tire_brand,
                            'tire_size'=>$order_tire_list_old->tire_size,
                            'tire_pattern'=>$order_tire_list_old->tire_pattern,
                            'volume' => $order_tire_list_old->tire_number,
                            'sell_price' => $order_tire_list_old->tire_price,
                            'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                            'date_manufacture_sale' => $order_tire_list_old->tire_date,
                        );
                        $tire_sale_model->updateTire($data_sale,array('tire_sale_id'=>$tire_sale->tire_sale_id));

                        $obtain_data = array(
                            'obtain_date' => $order_tire_old->delivery_date,
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'order_tire' => $order_tire_list->order_tire,
                        );
                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }
                        $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                        $receivable_data = array(
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'receivable_date' => $order_tire_old->delivery_date,
                            'expect_date' => $order_tire_old->delivery_date,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'code' => $order_tire_old->order_number,
                            'source' => 1,
                            'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                            'create_user' => $user,
                            'type' => 4,
                            'order_tire' => $order_tire_list->order_tire,
                            'check_vat' => $order_tire_old->vat>0?1:0,
                        );

                        
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }

                        $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                    }

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|".$orderlist."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    $result = array(
                        'data'=>null,
                        'msg'=>'Cập nhật thành công',
                        'err'=>1
                    );
                }
                else{
                    $result = array(
                        'data'=>null,
                        'msg'=>'Mã hàng đã tồn tại',
                        'err'=>0
                    );
                }
            }
            else{
                if (!$order_tire_list_model->getTireByWhere(array('order_tire'=>$data['order_tire'], 'tire_brand'=>$brand, 'tire_size'=>$size, 'tire_pattern'=>$pattern))) {
                    $order_tire_list_model->createTire($data);

                    $order_tire_list = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                    $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                    $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                    $total_number = 0;
                    $total = 0;
                    $vat = 0;
                    foreach ($order_lists as $od) {
                        $total_number += $od->tire_number;
                        
                        if ($order_tire->check_price_vat == 1) {
                            $p = $od->tire_price_vat;
                            $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                            $n = $p-$v;

                            $vat += $v*$od->tire_number;
                            $total += $od->tire_number*$od->tire_price_vat;
                        }
                        else{
                            $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                        }
                    }

                    $discount = $order_tire->discount+$order_tire->reduce;
                    $warranty = round($total*$order_tire->warranty/100);
                    $total = $total - $discount - $warranty;

                    $data_order = array(
                        'total'=>$total,
                        'order_tire_number'=>$total_number,
                        'vat'=> $vat,
                        'warranty'=>$warranty,
                    );


                    $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                    if($order_tire->order_tire_status==1){

                        $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                        $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                        $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                        $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire_old->sale));

                        $check_vat = $order_tire_old->vat>0?1:0;
                        //$vat = $order->tire_price*$order_tire->vat_percent/100;
                        $data_sale = array(
                                
                            'code' => $order_tire_old->order_number,
                            'volume' => $order_tire_list_old->tire_number,
                            'tire_brand' => $order_tire_list_old->tire_brand,
                            'tire_size' => $order_tire_list_old->tire_size,
                            'sell_price' => $order_tire_list_old->tire_price,
                            'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                            'customer' => $order_tire_old->customer,
                            'tire_sale_date' => $order_tire_old->delivery_date,
                            //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                            'tire_pattern' => $order_tire_list_old->tire_pattern,
                            'check_vat' => $check_vat,
                            'sale' => $staff->staff_id,
                            'customer_type' => $order_tire_old->customer_type,
                            'order_tire' => $order_tire_list->order_tire,
                            'date_manufacture_sale' => $order_tire_list_old->tire_date,
                        );
                        $tire_sale_model->createTire($data_sale);

                        $obtain_data = array(
                            'obtain_date' => $order_tire_old->delivery_date,
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'order_tire' => $order_tire_list->order_tire,
                        );
                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }
                        $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                        $receivable_data = array(
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'receivable_date' => $order_tire_old->delivery_date,
                            'expect_date' => $order_tire_old->delivery_date,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'code' => $order_tire_old->order_number,
                            'source' => 1,
                            'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                            'create_user' => $user,
                            'type' => 4,
                            'order_tire' => $order_tire_list->order_tire,
                            'check_vat' => $order_tire_old->vat>0?1:0,
                        );

                        
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }

                        $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                    }


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$user."|"."add"."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    $result = array(
                        'data'=>null,
                        'msg'=>'Thêm thành công',
                        'err'=>1
                    );
                }
                else{
                    $result = array(
                        'data'=>null,
                        'msg'=>'Mã hàng đã tồn tại',
                        'err'=>0
                    );
                }
            }

            if ($order_tire->id_order_agent>0) {
                $tire_pattern_model = $this->model->get('tirepatternModel');
                $tire_brand_model = $this->model->get('tirebrandModel');
                $tire_size_model = $this->model->get('tiresizeModel');
                $customer_model = $this->model->get('customerModel');

                $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire->order_tire_id));
                $order_agent = array();
                $i=0;
                foreach ($order_lists as $order_list) {
                    $order_agent[$i]['tire_brand'] = $tire_brand_model->getTire($order_list->tire_brand)->tire_brand_name;
                    $order_agent[$i]['tire_size'] = $tire_size_model->getTire($order_list->tire_size)->tire_size_number;
                    $order_agent[$i]['tire_pattern'] = $tire_pattern_model->getTire($order_list->tire_pattern)->tire_pattern_name;
                    $order_agent[$i]['tire_number'] = $order_list->tire_number;
                    $order_agent[$i]['tire_price'] = $order_list->tire_price_vat;

                    $i++;
                }

                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/editagentorder';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                   'order_tire' => $order_agent,
                   'total_number'=>$order_tire->order_tire_number,
                   'total'=>$order_tire->total,
                   'order_number'=>$order_tire->order_number,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $re = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
        }


        echo json_encode($result);
    }
    public function exstock(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];
        $lift = $obj['staff'];
        $delivery = str_replace('/', '-', $obj['delivery']);
        $arrival = str_replace('/', '-', $obj['arrival']);

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $customer_model = $this->model->get('customerModel');
            $lift_model = $this->model->get('liftModel');


            $order_tire = $order_tire_model->getTire($id);

            $data = array(
                'order_tire_status'=>1,
                'delivery_date'=>strtotime($delivery),
                'arrival_date'=>strtotime($arrival),
                'sale_lock'=>1,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $week = (int)date('W',$data['delivery_date']);
            $year = (int)date('Y',$data['delivery_date']);

            if($week == 53){
                $week = 1;
                $year = $year+1;
            }
            if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                $year = (int)date('Y',$data['delivery_date'])+1;
            }

            $owe_model->updateOwe(array('owe_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $payable_model->updateCosts(array('payable_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));

            if ($order_tire->order_tire_status != 1) {
                  
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$id));
                $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire->sale));

                foreach ($order_tire_lists as $order) {
                    $check_vat = $order_tire->vat>0?1:0;
                    //$vat = $order->tire_price*$order_tire->vat_percent/100;
                    $data_sale = array(
                            
                        'code' => $order_tire->order_number,
                        'volume' => $order->tire_number,
                        'tire_brand' => $order->tire_brand,
                        'tire_size' => $order->tire_size,
                        'sell_price' => $order->tire_price,
                        'sell_price_vat' => $order->tire_price_vat,
                        'customer' => $order_tire->customer,
                        'tire_sale_date' => $data['delivery_date'],
                        //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                        'tire_pattern' => $order->tire_pattern,
                        'check_vat' => $check_vat,
                        'sale' => $staff->staff_id,
                        'customer_type' => $order_tire->customer_type,
                        'order_tire' => $id,
                        'date_manufacture_sale' => $order->tire_date,
                    );
                    $tire_sale_model->createTire($data_sale);
                }

                $obtain_data = array(
                    'obtain_date' => $data['delivery_date'],
                    'customer' => $order_tire->customer,
                    'money' => $order_tire->total,
                    'week' => (int)date('W',$data['delivery_date']),
                    'year' => (int)date('Y',$data['delivery_date']),
                    'order_tire' => $id,
                );
                if($obtain_data['week'] == 53){
                    $obtain_data['week'] = 1;
                    $obtain_data['year'] = $obtain_data['year']+1;
                }
                if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                    $obtain_data['year'] = (int)date('Y',$data['delivery_date'])+1;
                }
                $obtain_model->createObtain($obtain_data);

                $receivable_data = array(
                    'customer' => $order_tire->customer,
                    'money' => $order_tire->total,
                    'receivable_date' => $data['delivery_date'],
                    'expect_date' => $data['delivery_date'],
                    'week' => (int)date('W',$data['delivery_date']),
                    'year' => (int)date('Y',$data['delivery_date']),
                    'code' => $order_tire->order_number,
                    'source' => 1,
                    'comment' => $order_tire->order_tire_number.' lốp '.$order_tire->order_number,
                    'create_user' => $user,
                    'type' => 4,
                    'order_tire' => $id,
                    'check_vat' => $order_tire->vat>0?1:0,
                );

                
                if($receivable_data['week'] == 53){
                    $receivable_data['week'] = 1;
                    $receivable_data['year'] = $receivable_data['year']+1;
                }
                if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                    $receivable_data['year'] = (int)date('Y',$data['delivery_date'])+1;
                }

                $receivable_model->createCosts($receivable_data);


                


                if ($order_tire->id_order_agent>0) {
                    $customers = $customer_model->getCustomer($order_tire->customer);
                    // where are we posting to?
                    $url = $customers->customer_agent_link.'/ordertire/receiveorder';

                    // what post fields?
                    $fields = array(
                       'id_order_tire' => $order_tire->id_order_agent,
                       'delivery_date' => $data['delivery_date'],
                       'order_number' => $order_tire->order_number,
                    );
                    // build the urlencoded data
                    $postvars = http_build_query($fields);

                    // open connection
                    $ch = curl_init();

                    // set the url, number of POST vars, POST data
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, count($fields));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    // execute post
                    $result = curl_exec($ch);

                    // close connection
                    curl_close($ch);
                }

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$uder."|"."edit"."|exstock|".$id."|order_tire|"."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);

            }
            else{
                
                $tire_sale_model->updateTire(array('tire_sale_date' => $data['delivery_date']),array('order_tire'=>$id));
                $obtain_model->updateObtain(array('obtain_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
                $receivable_model->updateCosts(array('receivable_date'=>$data['delivery_date'],'expect_date' => $data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));


                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|exstock|".$id."|order_tire|"."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);

                
            }

            $contributor = "";
            if(trim($lift) != ""){
                $support = explode(',', trim($lift));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                        if ($contributor == "")
                            $contributor .= $name;
                        else
                            $contributor .= ','.$name;
                    }
                }

                $data_lift = array(
                    'lift_date' => $data['delivery_date'],
                    'staff' => $contributor,
                    'order_tire' => $id,
                );

                if ($lift_model->getLiftByWhere(array('order_tire'=>$id))) {
                    $lift_model->updateLift($data_lift,array('order_tire'=>$id));
                }
                else{
                    $lift_model->createLift($data_lift);
                }
                
            }
            else{
                $lift_model->queryLift('DELETE FROM lift WHERE order_tire = '.$id);
            }

            $result = array(
                'data' => null,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function revert(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            //$owe_model = $this->model->get('oweModel');
            //$payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            //$pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            $customer_model = $this->model->get('customerModel');

            $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$id));
            foreach ($re as $r) {
                $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
            }
            // $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
            // foreach ($pa as $p) {
            //     $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
            //     $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            // }

            $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$id);
            //$payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$_POST['data']);
            $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$id);
            //$owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$_POST['data']);
            $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$id);
            $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$id);

            $data = array(
                'order_tire_status'=>null,
                'delivery_date'=>null,
                'sale_lock'=>0,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));


            $order_tire = $order_tire_model->getTire($id);

            if ($order_tire->id_order_agent>0) {
                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/revertorder';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $res = curl_exec($ch);

                // close connection
                curl_close($ch);
            }

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|revert|".$id."|order_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function delete(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_cost_model = $this->model->get('ordertirecostModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
            $additional_model = $this->model->get('additionalModel');

            $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$id));
            foreach ($re as $r) {
                $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
            }
            $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$id));
            foreach ($pa as $p) {
                $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            }

            $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$id);
            $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$id);
            $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$id);
            $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$id);
            $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$id);
            $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$id);
            $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$id);
            $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$id);
            $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$id);
            $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE order_tire = '.$id);
            $additional_model->queryAdditional('DELETE FROM additional WHERE order_tire = '.$id);
            $order_tire_model->deleteTire($id);
            
            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."delete"."|".$id."|order_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Xóa thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }
    public function deletedetail(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_list = $order_tire_list_model->getTire($id);
            $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

            $order_tire_list_model->deleteTire($id);

            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
            $total_number = 0;
            $total = 0;
            $vat = 0;
            foreach ($order_lists as $od) {
                $total_number += $od->tire_number;
                
                if ($order_tire->check_price_vat == 1) {
                    $p = $od->tire_price_vat;
                    $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                    $n = $p-$v;

                    $vat += $v*$od->tire_number;
                    $total += $od->tire_number*$od->tire_price_vat;
                }
                else{
                    $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                    $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                }
            }

            $discount = $order_tire->discount+$order_tire->reduce;
            $warranty = round($total*$order_tire->warranty/100);
            $total = $total - $discount - $warranty;


            $data_order = array(
                'discount'=>$discount,
                'total'=>$total,
                'order_tire_number'=>$total_number,
                'vat'=> $vat,
                'warranty'=>$warranty,
            );


            $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

            if($order_tire->order_tire_status==1){

                $order_tire_list_old = $order_tire_list_model->getTire($id);

                $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE tire_brand = '.$order_tire_list_old->tire_brand.' AND tire_size = '.$order_tire_list_old->tire_size.' AND tire_pattern = '.$order_tire_list_old->tire_pattern.' AND order_tire = '.$order_tire_list->order_tire);

                $obtain_data = array(
                    'obtain_date' => $order_tire_old->delivery_date,
                    'customer' => $order_tire_old->customer,
                    'money' => $order_tire_old->total,
                    'week' => (int)date('W',$order_tire_old->delivery_date),
                    'year' => (int)date('Y',$order_tire_old->delivery_date),
                    'order_tire' => $order_tire_list->order_tire,
                );
                if($obtain_data['week'] == 53){
                    $obtain_data['week'] = 1;
                    $obtain_data['year'] = $obtain_data['year']+1;
                }
                if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                    $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                }
                $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                $receivable_data = array(
                    'customer' => $order_tire_old->customer,
                    'money' => $order_tire_old->total,
                    'receivable_date' => $order_tire_old->delivery_date,
                    'expect_date' => $order_tire_old->delivery_date,
                    'week' => (int)date('W',$order_tire_old->delivery_date),
                    'year' => (int)date('Y',$order_tire_old->delivery_date),
                    'code' => $order_tire_old->order_number,
                    'source' => 1,
                    'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                    'create_user' => $user,
                    'type' => 4,
                    'order_tire' => $order_tire_list->order_tire,
                    'check_vat' => $order_tire_old->vat>0?1:0,
                );

                
                if($receivable_data['week'] == 53){
                    $receivable_data['week'] = 1;
                    $receivable_data['year'] = $receivable_data['year']+1;
                }
                if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                    $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                }

                $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
            }

            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."delete"."|".$id."|order_tire_list|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Xóa thành công',
                'err' => 1
            );
        }

        echo json_encode($result);
    }

    public function staffs() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );


        $staff_model = $this->model->get('staffModel');

        $data = array(
            'where' => 'status=1',
            'field' => 'staff_id,staff_name,account'
        );

        $staffs = $staff_model->getAllStaff($data);

        $result = array(
            'data'=>$staffs,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }

    public function orders() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $batdau = str_replace('/', '-', $_GET['start']);
        $ketthuc = str_replace('/', '-', $_GET['end']);
        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $order_tire_model = $this->model->get('ordertireModel');

        $data = array(
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
            'order_by'=>'order_tire_status ASC, ABS(SUBSTRING(order_number,4)) DESC',
            'field'=>'order_tire_id,order_number,order_tire_number,vat,total,approve,delivery_date,arrival_date,sale,sale_cskh,sale_lock,order_tire_status,customer_id,customer_name,user_id,username'
        );
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire.customer AND user_id = sale');

        $orders = $order_tire_model->getAllTire($data,$join);

        $last = "";
        $lasts = $order_tire_model->getAllTire(array('order_by'=>'ABS(SUBSTRING(order_number,4,4)) DESC, ABS(SUBSTRING(order_number,4)) DESC','limit'=>1));
        foreach ($lasts as $tire) {
            $last = str_replace('lx-', '', $tire->order_number);
        }
        $last++;
        
        $str = 'lx-'.$last;

        $result = array(
            'data'=>$orders,
            'lastID'=>$str,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }
    public function detailorder() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $order = $_GET['order'];

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $data = array(
            'where'=>'order_tire='.$order,
            'field'=>'order_tire_list.*,tire_brand_name,tire_size_number,tire_pattern_name,sale_lock,check_price_vat,vat,vat_percent,approve,sale_cskh,sale'
        );
        $join = array('table'=>'order_tire, tire_brand, tire_size, tire_pattern','where'=>'order_tire=order_tire_id AND tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id');

        $orders = $order_tire_list_model->getAllTire($data,$join);

        $result = array(
            'data'=>$orders,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }
    public function detailorderlist() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $order = $_GET['order'];
        $orderlist = $_GET['orderlist'];

        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $order_tire_model = $this->model->get('ordertireModel');

        $arr = array();

        if ($orderlist>0) {
            $data = array(
                'where'=>'order_tire_list_id='.$orderlist,
                'field'=>'order_tire_list.*,tire_brand_name,tire_size_number,tire_pattern_name,order_number,sale_lock,check_price_vat,vat,vat_percent,approve,sale_cskh,sale'
            );
            $join = array('table'=>'order_tire, tire_brand, tire_size, tire_pattern','where'=>'order_tire=order_tire_id AND tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id');

            $orders = $order_tire_list_model->getAllTire($data,$join);
        }
        else{
            $data = array(
                'where'=>'order_tire_id='.$order,
                'field'=>'order_number'
            );

            $orders = $order_tire_model->getAllTire($data);
        }

        foreach ($orders as $tire) {
            $arr = $tire;
        }

        $result = array(
            'data'=>$arr,
            'msg'=>'',
            'err'=>null
        );
        

        echo json_encode($result);
    }
    public function maxorder() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $brand = $_GET['brand'];
        $size = $_GET['size'];
        $pattern = $_GET['pattern'];

        $ton=0; $dangve=0;

        if ($brand>0 && $size>0 && $pattern>0) {
            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tire_going_model = $this->model->get('tiregoingModel');

            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$brand.' AND tire_buy_size = '.$size.' AND tire_buy_pattern = '.$pattern));
            foreach ($tire_buys as $tire) {
                $ton += $tire->tire_buy_volume;
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_sales as $tire) {
                $ton -= $tire->volume;
            }

            $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tires as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
                foreach ($order_tire_lists as $list) {
                    $ton -= $list->tire_number;
                }
            }

            $tire_goings = $tire_going_model->getAllTire(array('where'=>'(status IS NULL OR status != 1 ) AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_goings as $going) {
                $dangve += $going->tire_number;
            }
        }
        

        $result = array(
            'data'=>$ton+$dangve,
            'msg'=>'',
            'err'=>null
        );
        

        echo json_encode($result);
    }
    public function brands() {
        $this->view->disableLayout();
        
        $result = array(
            'brand'=>null,
            'size'=>null,
            'pattern'=>null,
            'msg'=>'',
            'err'=>null
        );


        $import_tire_order_model = $this->model->get('importtireorderModel');
        $import_tire_list_model = $this->model->get('importtirelistModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');

        $tonkho = array();
        
        $tire_buys = $tire_buy_model->getAllTire();
        foreach ($tire_buys as $buy) {
            $tonkho['brand'][$buy->tire_buy_brand] = isset($tonkho['brand'][$buy->tire_buy_brand])?$tonkho['brand'][$buy->tire_buy_brand]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            $tonkho['size'][$buy->tire_buy_size] = isset($tonkho['size'][$buy->tire_buy_size])?$tonkho['size'][$buy->tire_buy_size]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            $tonkho['pattern'][$buy->tire_buy_pattern] = isset($tonkho['pattern'][$buy->tire_buy_pattern])?$tonkho['pattern'][$buy->tire_buy_pattern]+$buy->tire_buy_volume:$buy->tire_buy_volume;
        }

        $tire_sales = $tire_sale_model->getAllTire();
        foreach ($tire_sales as $sale) {
            $tonkho['brand'][$sale->tire_brand] = isset($tonkho['brand'][$sale->tire_brand])?$tonkho['brand'][$sale->tire_brand]-$sale->volume:(0-$sale->volume);
            $tonkho['size'][$sale->tire_size] = isset($tonkho['size'][$sale->tire_size])?$tonkho['size'][$sale->tire_size]-$sale->volume:(0-$sale->volume);
            $tonkho['pattern'][$sale->tire_pattern] = isset($tonkho['pattern'][$sale->tire_pattern])?$tonkho['pattern'][$sale->tire_pattern]-$sale->volume:(0-$sale->volume);
        }

     
        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status=2 OR import_tire_order_status=4)','order_by'=>'import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id)); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $tonkho['brand'][$going->tire_brand] = isset($tonkho['brand'][$going->tire_brand])?$tonkho['brand'][$going->tire_brand]+$going->tire_number:$going->tire_number;
                $tonkho['size'][$going->tire_size] = isset($tonkho['size'][$going->tire_size])?$tonkho['size'][$going->tire_size]+$going->tire_number:$going->tire_number;
                $tonkho['pattern'][$going->tire_pattern] = isset($tonkho['pattern'][$going->tire_pattern])?$tonkho['pattern'][$going->tire_pattern]+$going->tire_number:$going->tire_number;
            }
        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        $brands = array();
        $sizes = array();
        $patterns = array();

        foreach ($tire_brands as $brand) {
            if (isset($tonkho['brand'][$brand->tire_brand_id]) && $tonkho['brand'][$brand->tire_brand_id] > 0) {
                $brands[] = $brand;
            }
        }
        foreach ($tire_sizes as $size) {
            if (isset($tonkho['size'][$size->tire_size_id]) && $tonkho['size'][$size->tire_size_id] > 0) {
                $sizes[] = $size;
            }
        }
        foreach ($tire_patterns as $key => $pattern) {
            if (isset($tonkho['pattern'][$pattern->tire_pattern_id]) && $tonkho['pattern'][$pattern->tire_pattern_id] > 0) {
                $patterns[] = $pattern;
            }
        }


        $result = array(
            'brand'=>$brands,
            'size'=>$sizes,
            'pattern'=>$patterns,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }
    public function prices() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $tire_price_discount_model = $this->model->get('tirepricediscountModel');

        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id');
        $data = array(
            'where'=> 'start_date <= '.strtotime(date('d-m-Y')).' AND (end_date IS NULL OR end_date=0 OR end_date >= '.strtotime(date('d-m-Y')).')',
            'order_by'=>'tire_brand_name ASC, tire_size_number ASC, tire_pattern_type ASC, tire_pattern_name ASC',
        );

        $prices = $tire_price_discount_model->getAllTire($data,$join);

        $result = array(
            'data'=>$prices,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }
    public function stock() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $import_tire_order_model = $this->model->get('importtireorderModel');
        $import_tire_list_model = $this->model->get('importtirelistModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $hangorder = array();
        $tonkho = array();
        $dathang = array();
        $dangve = array();
        
        $orders = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status IS NULL OR import_tire_order_status=1 OR import_tire_order_status=0)','order_by'=>'import_tire_order_date ASC'));
        foreach ($orders as $order) {
            $tire_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$order->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($tire_orders as $tire_order) {
                $hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name] = isset($hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name])?$hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name]+$tire_order->tire_number:$tire_order->tire_number;
            }
        }
        

        $tire_buys = $tire_buy_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_buy_pattern=tire_pattern_id and tire_buy_size=tire_size_id AND tire_buy_brand=tire_brand_id'));
        foreach ($tire_buys as $buy) {
            $tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name] = isset($tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name])?$tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name]+$buy->tire_buy_volume:$buy->tire_buy_volume;
        }

        $tire_sales = $tire_sale_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_pattern=tire_pattern_id and tire_size=tire_size_id AND tire_brand=tire_brand_id'));
        foreach ($tire_sales as $sale) {
            $tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name] = isset($tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name])?$tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name]-$sale->volume:(0-$sale->volume);
        }

        $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
        foreach ($order_tires as $order) {
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id'));
            foreach ($order_tire_lists as $list) {
                $dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name] = isset($dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name])?$dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name]+$list->tire_number:$list->tire_number;
            }
        }


        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status=2 OR import_tire_order_status=4)','order_by'=>'import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
            }
        }

        $data = array(
            "Goodride@7.50R16@CR926",
            "Goodride@9.00-20@CL946",
            "Goodride@10.00R20@CR926",
            "Goodride@11.00R20@CR926",
            "Goodride@11.00R20@CR976A",
            "Goodride@11.00R20@CM987",
            "Goodride@11.00R20@CB972",
            "Goodride@12.00R20@CR926",
            "Goodride@12.00R20@CM913A",
            "Goodride@12.00R20@EZ356",
            "Goodride@11R22.5@CR976A",
            "Goodride@11R22.5@AS668",
            "Goodride@11R22.5@CM983",
            "Goodride@12R22.5@CR926",
            "Goodride@12R22.5@CR976A",
            "Goodride@12R22.5@AS668",
            "Goodride@12R22.5@MD738",
            "Goodride@295/75R22.5@CR960A",
            "Goodride@295/75R22.5@CM983"
        );

        $result = array();

        $tongton=0;
        $tongdangve=0;
        $tongdathang=0;
        $tongorder=0;
        $i=1;
        foreach ($data as $key) {
            $arr = explode("@", $key);
            $str = str_replace('@', '', $key);

            $ton = isset($tonkho[$str])?$tonkho[$str]:'';
            $ve = isset($dangve[$str])?$dangve[$str]:'';
            $dat = isset($dathang[$str])?$dathang[$str]:'';
            $or = isset($hangorder[$str])?$hangorder[$str]:'';

            $tongton+=$ton;
            $tongdangve+=$ve;
            $tongdathang+=$dat;
            $tongorder+=$or;

            $result[] = array(
                'tire_id'=>(string)$i++,
                'tire_brand_name'=>$arr[0],
                'tire_size_number'=>$arr[1],
                'tire_pattern_name'=>$arr[2],
                'tonkho'=>$ton,
                'dathang'=>$dat,
                'dangve'=>$ve,
                'order'=>$or
            );
        }

        $result[] = array(
            'tire_id'=>(string)$i,
            'tire_brand_name'=>'',
            'tire_size_number'=>'',
            'tire_pattern_name'=>'',
            'tonkho'=>'Tổng tồn kho: '.$tongton,
            'dathang'=>'Tổng đặt hàng: '.$tongdathang,
            'dangve'=>'Tổng đang về: '.$tongdangve,
            'order'=>'Tổng order: '.$tongorder,
        );

        $result = array(
            'data'=>$result,
            'msg'=>'',
            'err'=>null
        );

        echo json_encode($result);
    }
    public function customer() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $id = $_GET['id'];

        $customer_model = $this->model->get('customerModel');

        $customers = $customer_model->getCustomer($id);
        if ($customers) {
            $result = array(
                'data'=>$customers,
                'msg'=>'',
                'err'=>1
            );
        }

        echo json_encode($result);
    }
    public function getcustomer(){
        $this->view->disableLayout();
        
        $keyword = trim($_GET['keyword']);
        $user = trim($_GET['user']);
        $role = trim($_GET['role']);
        
        $result = array(
            'data'=>array(),
            'msg'=>'',
            'err'=>null
        );
        if ($keyword != "" && $user > 0 && $role > 0) {
            $customer_model = $this->model->get('customerModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            
            $list = array();

            if ($role != 1 && $role != 2 && $role != 8 && $role != 9) {
                if ($keyword == "*") {
                    $data = array(
                    'where'=>'customer_create_user='.$user.' OR customer_care='.$user,
                    'order_by'=>'customer_name ASC'
                    );
                }
                else{
                    $data = array(
                    'where'=>'(customer_create_user='.$user.' OR customer_care='.$user.') AND ( customer_name LIKE "%'.$keyword.'%" )',
                    'order_by'=>'customer_name ASC'
                    );
                }
            }
            else{
                if ($keyword == "*") {
                    $data = array(
                    'order_by'=>'customer_name ASC'
                    );
                }
                else{
                    $data = array(
                    'where'=>'( customer_name LIKE "%'.$keyword.'%" )',
                    'order_by'=>'customer_name ASC'
                    );
                }
            }

            $list = $customer_model->getAllCustomer($data);
            
            
            $result = array(
                'data'=>$list,
                'msg'=>'',
                'err'=>1
            );
        }
        echo json_encode($result);
        
    }
    public function getcustomerinfo(){
        $this->view->disableLayout();
        

        $company = trim($_GET['company']);
        $mst = trim($_GET['mst']);
        $phone = trim($_GET['phone']);
        $email = trim($_GET['email']);
        $type = trim($_GET['type']);
        $staff = trim($_GET['staff']);
        $customer_name = trim($_GET['customer_name']);
        $customer = trim($_GET['customer']);
        $address = trim($_GET['address']);
        $contact = trim($_GET['contact']);

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $tire_sale_model = $this->model->get('tiresaleModel');
        $customer_model = $this->model->get('customerModel');
        

        $arr = array(
            'company'=>$company,
            'mst'=>$mst,
            'phone'=>$phone,
            'email'=>$email,
            'type'=>$type,
            'staff'=>$staff,
            'customer_name'=>$customer_name,
            'customer'=>$customer,
            'address'=>$address,
            'contact'=>$contact,
        );

        if ($customer != "") {
            $customers = $customer_model->getCustomerByWhere(array('customer_id'=>$customer));
            if ($customers) {
                $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                if ($types) {
                    foreach ($types as $t) {
                        $type = $t->customer_type;
                    }
                }
                else{
                    $type = 1;
                }

                $arr = array(
                    'company'=>$customers->company_name,
                    'mst'=>$customers->mst,
                    'phone'=>$customers->customer_phone,
                    'email'=>$customers->customer_email,
                    'type'=>$type,
                    'staff'=>$customers->customer_create_user,
                    'customer_name'=>$customers->customer_name,
                    'customer'=>$customers->customer_id,
                    'address'=>$customers->customer_address,
                    'contact'=>$customers->customer_contact,
                );
            }
        }
        else if ($company != "") {
            $customers = $customer_model->getCustomerByWhere(array('company_name'=>$company));
            if ($customers) {
                $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                if ($types) {
                    foreach ($types as $t) {
                        $type = $t->customer_type;
                    }
                }
                else{
                    $type = 1;
                }

                $arr = array(
                    'company'=>$customers->company_name,
                    'mst'=>$customers->mst,
                    'phone'=>$customers->customer_phone,
                    'email'=>$customers->customer_email,
                    'type'=>$type,
                    'staff'=>$customers->customer_create_user,
                    'customer_name'=>$customers->customer_name,
                    'customer'=>$customers->customer_id,
                    'address'=>$customers->customer_address,
                    'contact'=>$customers->customer_contact,
                );
            }
        }
        else if ($mst != "") {
            $customers = $customer_model->getCustomerByWhere(array('mst'=>$mst));
            if ($customers) {
                $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                if ($types) {
                    foreach ($types as $t) {
                        $type = $t->customer_type;
                    }
                }
                else{
                    $type = 1;
                }

                $arr = array(
                    'company'=>$customers->company_name,
                    'mst'=>$customers->mst,
                    'phone'=>$customers->customer_phone,
                    'email'=>$customers->customer_email,
                    'type'=>$type,
                    'staff'=>$customers->customer_create_user,
                    'customer_name'=>$customers->customer_name,
                    'customer'=>$customers->customer_id,
                    'address'=>$customers->customer_address,
                    'contact'=>$customers->customer_contact,
                );
            }
        }
        else if ($phone != "") {
            $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>$phone));
            if (!$customers) {
                $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>str_replace(' ', '', $phone)));
            }
            if ($customers) {
                $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                if ($types) {
                    foreach ($types as $t) {
                        $type = $t->customer_type;
                    }
                }
                else{
                    $type = 1;
                }

                $arr = array(
                    'company'=>$customers->company_name,
                    'mst'=>$customers->mst,
                    'phone'=>$customers->customer_phone,
                    'email'=>$customers->customer_email,
                    'type'=>$type,
                    'staff'=>$customers->customer_create_user,
                    'customer_name'=>$customers->customer_name,
                    'customer'=>$customers->customer_id,
                    'address'=>$customers->customer_address,
                    'contact'=>$customers->customer_contact,
                );
            }
        }
        else if ($email != "") {
            $customers = $customer_model->getCustomerByWhere(array('customer_email'=>$email));
            if ($customers) {
                $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                if ($types) {
                    foreach ($types as $t) {
                        $type = $t->customer_type;
                    }
                }
                else{
                    $type = 1;
                }

                $arr = array(
                    'company'=>$customers->company_name,
                    'mst'=>$customers->mst,
                    'phone'=>$customers->customer_phone,
                    'email'=>$customers->customer_email,
                    'type'=>$type,
                    'staff'=>$customers->customer_create_user,
                    'customer_name'=>$customers->customer_name,
                    'customer'=>$customers->customer_id,
                    'address'=>$customers->customer_address,
                    'contact'=>$customers->customer_contact,
                );
            }
        }

        $result = array(
            'data'=>$arr,
            'msg'=>'',
            'err'=>1
        );

        echo json_encode($result);
    }

    public function createPDF(){
        $id = $this->registry->router->param_id;
        $type = $this->registry->router->page;

        if ($type==1) {
            $c = curl_init(BASE_URL.'/ordertire/printview/'.$id);
        }
        else{
            $c = curl_init(BASE_URL.'/ordertire/printview2/'.$id);
        }
        
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($c);

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ .'/public/temp']);
        $mpdf->WriteHTML($html);
        $mpdf->Output();
    }
    public function checklockuser(){

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if (isset($_GET['id'])) {
            $user_model = $this->model->get('userModel');

            $user = $user_model->getUserByWhere(array('user_id' => $_GET['id']));
            
            $result = array(
                'data'=>$user,
                'msg'=>'',
                'err'=>1
            );
        }
        

        echo json_encode($result);
    }
    
}
?>