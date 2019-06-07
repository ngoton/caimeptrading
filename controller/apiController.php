<?php
require "vendor/autoload.php";

Class apiController Extends baseController {
    public function index() {
        $this->view->disableLayout();
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function adddiscount(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $id = $obj['id'];
        $user = $obj['user'];
        $discount = str_replace('.','',$obj['discount']);
        $discount = str_replace(',','.',$discount);
        $reduce = str_replace('.','',$obj['reduce']);
        $reduce = str_replace(',','.',$reduce);
        $warranty = str_replace('.','',$obj['warranty']);
        $warranty = str_replace(',','.',$warranty);

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($id>0 && $user>0) {
            $order_tire_model = $this->model->get('ordertireModel');

            $order = $order_tire_model->getTire($id);
            $gia = $order->total+$order->discount+$order->reduce+$order->warranty;
            $total = $order->total+$order->discount+$order->reduce+$order->warranty-$discount-$reduce-$warranty;
            $percent = round(($warranty*100/$gia)*100)/100;
            
            $data = array(
                'discount'=>$discount,
                'reduce'=>$reduce,
                'warranty'=>$warranty,
                'warranty_percent'=>$percent,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));


            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|discount|".$id."|order_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => $total,
                'msg' => 'Cập nhật thành công',
                'err' => 1
            );
        }

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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
                                    $phones = $customer_model->queryCustomer('SELECT * FROM customer WHERE REPLACE(customer_phone, " ", "") = '.$phone);
                                    foreach ($phones as $val) {
                                        $cus = $val;
                                    }
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
        
        header("Content-Type: application/json");
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

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($order>0 && $user>0 && $brand>0 && $size>0 && $pattern>0 && $number>0) {
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
                    $warranty = round($total*$order_tire->warranty_percent/100);
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
                    $warranty = round($total*$order_tire->warranty_percent/100);
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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
            $warranty = round($total*$order_tire->warranty_percent/100);
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

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function deleteordercost(){
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
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');
            $assets = $this->model->get('assetsModel');
            $pay = $this->model->get('payModel');

            $order_tire_cost = $order_cost_model->getTire($id);
            $order_tire = $order_tire_model->getTire($order_tire_cost->order_tire);

            $order_tire_model->updateTire(array('order_cost'=>$order_tire->order_cost-$order_tire_cost->order_tire_cost),array('order_tire_id'=>$order_tire->order_tire_id));

            $p = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$order_tire_cost->order_tire_cost,'vendor'=>$order_tire_cost->vendor,'order_tire'=>$order_tire_cost->order_tire,'cost_type'=>$order_tire_cost->order_tire_cost_type));
            $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$order_tire_cost->order_tire.' AND vendor = '.$order_tire_cost->vendor.' AND money = '.$order_tire_cost->order_tire_cost);
            
            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            $payable_model->queryCosts('DELETE FROM payable WHERE payable_id='.$p->payable_id);

            $order_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire_cost_id = '.$order_tire_cost->order_tire_cost_id);

            

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$user."|"."delete"."|".$id."|order_tire_cost|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);

            $result = array(
                'data' => null,
                'msg' => 'Xóa thành công',
                'err' => 1
            );
        }

        header("Content-Type: application/json");
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
            'err'=>1
        );

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function vendors() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );


        $shipment_vendor_model = $this->model->get('shipmentvendorModel');

        $data = array(
            'order_by'=>'shipment_vendor_name',
            'order'=>'ASC',
            'field' => 'shipment_vendor_id,shipment_vendor_name'
        );

        $vendors = $shipment_vendor_model->getAllVendor($data);

        $result = array(
            'data'=>$vendors,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
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
            'field'=>'order_tire_id,order_number,order_tire_number,vat,total,approve,delivery_date,arrival_date,sale,sale_cskh,sale_lock,order_tire_status,customer_name,user_id,username,discount,reduce,warranty,order_cost,customer,check_price_vat,vat_percent,customer_type'
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

        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
        $tire_list_model = $this->model->get('ordertirelistModel');
        $tiresale_model = $this->model->get('tiresaleModel');
        $check_salary_percent_model = $this->model->get('checksalarypercentModel');

        $qr = 'SELECT * FROM check_salary_percent WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC LIMIT 1';
        $check_salarys = $check_salary_percent_model->querySalary($qr);
        $arr_salary = array();
        foreach ($check_salarys as $check_salary) {
            $arr_salary['sanluong'] = $check_salary->order_number;
            $arr_salary['moi'] = $check_salary->order_new;
            $arr_salary['cu'] = $check_salary->order_old;
            $arr_salary['phantram'] = $check_salary->order_percent;
        }

        $arr = array();
        $order_tire_discount = array();
        $this_month = array();
        $last_month = array();
        $bonus_salary = array();

        foreach ($orders as $tire) {

            $ngay = $tire->delivery_date>0?$tire->delivery_date:strtotime(date('d-m-Y'));

            $total_order_before = 0; //Tổng sản lượng tháng trước
            $total_order = 0; //Tổng sản lượng tháng này

            $myDate = strtotime(date("d-m-Y", $ngay) . "-1 month" ) ;

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$myDate)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$myDate)).' GROUP BY customer');
                
            foreach ($sum_order as $sum) {
                $total_order_before = $sum->tong;
            }

            ////////

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$ngay)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$ngay)).' GROUP BY customer');
            foreach ($sum_order as $sum) {
                $total_order = $sum->tong;
            }
            if ($total_order == 0) {
                $total_order = $tire->order_tire_number;
            }

            $last_month[$tire->order_tire_id] = $total_order_before;
            $this_month[$tire->order_tire_id] = $total_order;

            // if ($total_order_before>0) {
            //     $choose = $total_order_before;
            //     if ($tire->order_tire_number > $total_order_before) {
            //         $choose = $tire->order_tire_number;
            //     }

            //     if ($choose<20) {
            //         $column = "tire_retail";
            //     }
            //     else if ($choose<40) {
            //         $column = "tire_20";
            //     }
            //     else if ($choose<60) {
            //         $column = "tire_40";
            //     }
            //     else if ($choose<80) {
            //         $column = "tire_60";
            //     }
            //     else if ($choose<100) {
            //         $column = "tire_80";
            //     }
            //     else if ($choose<120) {
            //         $column = "tire_100";
            //     }
            //     else if ($choose<150) {
            //         $column = "tire_120";
            //     }
            //     else if ($choose<180) {
            //         $column = "tire_150";
            //     }
            //     else if ($choose<220) {
            //         $column = "tire_180";
            //     }
            //     else {
            //         $column = "tire_cont";
            //     }
            // }
            // else{
                if ($total_order<20) {
                    $column = "tire_retail";
                    $column_agent = "tire_agent_10";
                }
                else if ($total_order<40) {
                    $column = "tire_20";
                    $column_agent = "tire_agent_20";
                }
                else if ($total_order<60) {
                    $column = "tire_40";
                    $column_agent = "tire_agent_40";
                }
                else if ($total_order<80) {
                    $column = "tire_60";
                    $column_agent = "tire_agent_60";
                }
                else if ($total_order<100) {
                    $column = "tire_80";
                    $column_agent = "tire_agent_80";
                }
                else if ($total_order<120) {
                    $column = "tire_100";
                    $column_agent = "tire_agent_100";
                }
                else if ($total_order<150) {
                    $column = "tire_120";
                    $column_agent = "tire_agent_120";
                }
                else if ($total_order<180) {
                    $column = "tire_150";
                    $column_agent = "tire_agent_150";
                }
                else if ($total_order<220) {
                    $column = "tire_180";
                    $column_agent = "tire_agent_180";
                }
                else {
                    $column = "tire_cont";
                    $column_agent = "tire_agent_cont";
                }
            //}

            $tongchiphi = $tire->order_cost+$tire->discount+$tire->reduce;
            $tongsoluong = $tire->order_tire_number;      
            
            $data = array(
                'where' => 'tire_number>0 AND order_tire = '.$tire->order_tire_id,
            );
            $sales = $tire_list_model->getAllTire($data);
            foreach ($sales as $sale) {
                if ($sale->tire_price_vat=="" || $sale->tire_price_vat==0) {
                    $dongia = $sale->tire_price+($sale->tire_price*$tire->vat_percent/100);
                }
                else{
                    if ($tire->check_price_vat==1) {
                        $dongia = $sale->tire_price_vat;
                    }
                    else{
                        $dongia = $sale->tire_price+($sale->tire_price*$tire->vat_percent/100);
                    }
                }
                $tire_price_origin = $dongia;

                $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
                $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

                
                $data_q = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

                $data_e = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );

                $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);

                $tire_prices = $dongia;
                $tire_price_agents = $dongia;
                $giacongkhai = $dongia;

                foreach ($tire_price_discounts as $price) {
                    $tire_prices = $price->$column;
                    $tire_price_agents = $price->$column_agent;
                    $tire_price_origin = ($price->tire_price*0.75); // giá công khai giảm 25% + vc
                    $giacongkhai = $price->tire_price;

                    foreach ($tire_price_discount_events as $event) {
                        if ($event->percent_discount > 0) {
                            $tire_prices = $price->$column*((100-$event->percent_discount)/100);
                            $tire_price_agents = $price->$column_agent*((100-$event->percent_discount)/100);
                            $tire_price_origin = ($price->tire_price*0.75)*((100-$event->percent_discount)/100);
                            $giacongkhai = $price->tire_price*((100-$event->percent_discount)/100);
                        }
                        else{
                            $tire_prices = $price->$column-$event->money_discount;
                            $tire_price_agents = $price->$column_agent-$event->money_discount;
                            $tire_price_origin = ($price->tire_price*0.75)-$event->money_discount;
                            $giacongkhai = $price->tire_price-$event->money_discount;
                        }
                    }
                }

                $chiphi = $tongchiphi/$tongsoluong-77000;
                //$chiphi = $chiphi>0?$chiphi:0;
                
                $gia = $dongia-$chiphi;
                $dongia = $dongia-$chiphi;

                if ($tire->customer_type==1) {
                    if ($tire->vat==0) {
                        if ($tire_price_agents<5000000) {
                            $discount = 160000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }
                }
                else{
                    if ($tire->vat==0) {
                        if ($tire_prices<5000000) {
                            $discount = 160000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }
                }
                

                $order_tire_discount[$tire->order_tire_id]['thu'] = isset($order_tire_discount[$tire->order_tire_id]['thu'])?$order_tire_discount[$tire->order_tire_id]['thu']+$gia*$sale->tire_number:$gia*$sale->tire_number;
                $order_tire_discount[$tire->order_tire_id]['gia'] = isset($order_tire_discount[$tire->order_tire_id]['gia'])?$order_tire_discount[$tire->order_tire_id]['gia']+$giacongkhai*$sale->tire_number:$giacongkhai*$sale->tire_number;

                $salary = (($gia-$tire_price_origin)*$arr_salary['phantram']/100)*$sale->tire_number;

                if ($dongia < $giacongkhai*0.748) {
                    $salary = 0;
                }
                else{
                    if ($tire->customer_type==1) {
                        $quydinh = $tire_price_agents*100/$giacongkhai;
                    }
                    else{
                        $quydinh = $tire_prices*100/$giacongkhai;
                    }
                    
                    $ban = $dongia*100/$giacongkhai;
                    if ($ban < ($quydinh-4)) {
                        $salary = $arr_salary['sanluong']*$sale->tire_number;
                    }

                    if ($salary < $arr_salary['sanluong']*$sale->tire_number) {
                        $salary = $arr_salary['sanluong']*$sale->tire_number;
                    }
                }

                $bonus_salary[$tire->order_tire_id] = isset($bonus_salary[$tire->order_tire_id])?$bonus_salary[$tire->order_tire_id]+$salary:$salary;
            }

            $tire->last_month = $last_month[$tire->order_tire_id];
            $tire->this_month = $this_month[$tire->order_tire_id];

            $tire->percent = "";
            $tire->salary = "";
            if($tire->order_tire_number>0){
                $giaban = $order_tire_discount[$tire->order_tire_id]['thu'];
                $giacongkhai = $order_tire_discount[$tire->order_tire_id]['gia'];
                $giam = round((($giacongkhai-$giaban)/$giacongkhai)*100,1);

                $tire->percent = $giam;
                $tire->salary = round($bonus_salary[$tire->order_tire_id]);
            }
            

            $arr[] = $tire;
        }

        $result = array(
            'data'=>$arr,
            'lastID'=>$str,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
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
            'field'=>'order_tire_list.*,tire_brand_name,tire_size_number,tire_pattern_name,order_number,sale_lock,check_price_vat,vat,vat_percent,approve,sale_cskh,sale'
        );
        $join = array('table'=>'order_tire, tire_brand, tire_size, tire_pattern','where'=>'order_tire=order_tire_id AND tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id');

        $orders = $order_tire_list_model->getAllTire($data,$join);

        $result = array(
            'data'=>$orders,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
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
                'field'=>'order_number,sale_lock'
            );

            $orders = $order_tire_model->getAllTire($data);
        }

        foreach ($orders as $tire) {
            $arr = $tire;
        }

        $result = array(
            'data'=>$arr,
            'msg'=>'',
            'err'=>1
        );
        
        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function ordercost() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $order = $_GET['order'];

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire = $order_tire_model->getTire($order);

        $order_tire_cost_model = $this->model->get('ordertirecostModel');
        $join = array('table'=>'shipment_vendor','where'=>'vendor=shipment_vendor_id');

        $data = array(
            'where' => 'order_tire='.$order,
            'fields' => 'order_tire_cost_id,vendor,order_tire_cost_type,shipment_vendor_name,comment,order_tire_cost_create_user,order_tire_cost,order_tire'
        );
        $order_tire_costs = $order_tire_cost_model->getAllTire($data,$join);

        $payable_model = $this->model->get('payableModel');

        $costs = array();
        foreach ($order_tire_costs as $order) {
            $payables = $payable_model->getCostsByWhere(array('order_tire'=>$order->order_tire,'vendor'=>$order->vendor,'cost_type'=>$order->order_tire_cost_type));
            switch($order->order_tire_cost_type){
              case 1: {$type = 'Trucking'; break;}
              case 2: {$type = 'Barging'; break;}
              case 3: {$type = 'Feeder'; break;}
              case 4: {$type = 'Thu hộ'; break;}
              case 5: {$type = 'Hoa hồng'; break;}
              case 6: {$type = 'TTHQ'; break;}
              case 7: {$type = 'Khác'; break;}
              default: {
                break;
              }
            };
            $costs[] = array(
                'order_tire_cost_id'=>$order->order_tire_cost_id,
                'order_tire'=>$order->order_tire,
                'cost_type'=>$type,
                'shipment_vendor_name'=>$order->shipment_vendor_name,
                'comment'=>$order->comment,
                'order_tire_cost'=>$order->order_tire_cost,
                'pay_date'=>$payables->pay_date,
                'create_user'=>$order->order_tire_cost_create_user,
                'sale_lock'=>$order_tire->sale_lock
            );
        }

        $result = array(
            'data'=>$costs,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function detailordercost() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $order = $_GET['order'];
        $ordercost = $_GET['ordercost'];

        $order_tire_cost_model = $this->model->get('ordertirecostModel');
        $order_tire_model = $this->model->get('ordertireModel');

        $arr = array();

        if ($ordercost>0) {
            $data = array(
                'where'=>'order_tire_cost_id='.$ordercost,
                'field'=>'order_tire_cost.*,sale_lock,shipment_vendor_name,order_number'
            );
            $join = array('table'=>'shipment_vendor,order_tire','where'=>'vendor=shipment_vendor_id AND order_tire=order_tire_id');

            $orders = $order_tire_cost_model->getAllTire($data,$join);
        }
        else{
            $data = array(
                'where'=>'order_tire_id='.$order,
                'field'=>'order_number,sale_lock'
            );

            $orders = $order_tire_model->getAllTire($data);
        }

        foreach ($orders as $tire) {
            $arr = $tire;
        }

        $result = array(
            'data'=>$arr,
            'msg'=>'',
            'err'=>1
        );
        
        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function editordercost(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $order = $obj['order'];
        $user = $obj['user'];
        $ordercost = $obj['ordercost'];
        $order_tire_cost_type = $obj['type'];
        $vendor = $obj['vendor'];
        $comment = trim($obj['comment']);
        $order_tire_cost = str_replace('.','',$obj['order_tire_cost']);
        $order_tire_cost = str_replace(',','.',$order_tire_cost);
        $order_tire_cost_date = strtotime(date('d-m-Y'));

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($order>0 && $user>0 && $order_tire_cost_type>0 && $vendor>0 && $order_tire_cost>0) {
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');

            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');

            $data_order = $order_tire_model->getTire($order);

            $cost = null;
            $cost_data = array(
                'order_tire' => $order,
                'order_tire_cost' => $order_tire_cost,
                'order_tire_cost_date' => $order_tire_cost_date,
                'vendor' => $vendor,
                'comment' => $comment,
                'order_tire_cost_type' => $order_tire_cost_type,
                'order_tire_cost_create_user' => $user,
            );
            $cost += $cost_data['order_tire_cost'];

            $owe_data = array(
                'owe_date' => $data_order->delivery_date,
                'vendor' => $cost_data['vendor'],
                'money' => $cost_data['order_tire_cost'],
                'week' => (int)date('W',$data_order->delivery_date),
                'year' => (int)date('Y',$data_order->delivery_date),
                'order_tire' => $order,
            );
            if($owe_data['week'] == 53){
                $owe_data['week'] = 1;
                $owe_data['year'] = $owe_data['year']+1;
            }
            if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                $owe_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
            }

            $payable_data = array(
                'vendor' => $cost_data['vendor'],
                'money' => $cost_data['order_tire_cost'],
                'payable_date' => $data_order->delivery_date,
                'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                'expect_date' => $cost_data['order_tire_cost_date'],
                'week' => (int)date('W',$data_order->delivery_date),
                'year' => (int)date('Y',$data_order->delivery_date),
                'code' => $data_order->order_number,
                'source' => 1,
                'comment' => $data_order->order_number.'-'.$cost_data['comment'],
                'create_user' => $user,
                'type' => 4,
                'order_tire' => $order,
                'cost_type' => $cost_data['order_tire_cost_type'],
                'approve' => null,
                'check_cost'=>4,
            );
            if($payable_data['week'] == 53){
                $payable_data['week'] = 1;
                $payable_data['year'] = $payable_data['year']+1;
            }
            if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                $payable_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
            }

            if ($ordercost>0) {
                if (!$order_cost_model->getAllTireByWhere($ordercost.' AND order_tire='.$order.' AND order_tire_cost_type='.$order_tire_cost_type.' AND vendor='.$vendor)) {
                    $data_order_cost = $order_cost_model->getTire($ordercost);
                    $order_cost_model->updateTire($cost_data,array('order_tire_cost_id'=>$data_order_cost->order_tire_cost_id));

                    $owe_model->updateOwe($owe_data,array('order_tire'=>$order,'vendor'=>$cost_data['vendor'],'money'=>$data_order_cost->order_tire_cost));
         
                    if($payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $data_order_cost->vendor,'order_tire'=>trim($order),'cost_type' => $data_order_cost->order_tire_cost_type))){
                        $check = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $data_order_cost->vendor,'order_tire'=>trim($order),'cost_type' => $data_order_cost->order_tire_cost_type));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable_model->updateCosts($payable_data,array('payable_id'=>$check->payable_id));
                        
                    }

                    $order_tire_model->updateTire(array('order_cost'=>$cost+($data_order->order_cost-$data_order_cost->order_tire_cost)),array('order_tire_id'=>$order));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$user."|"."add"."|".$data_order_cost->order_tire_cost_id."|order_tire_cost_|"."\n"."\r\n";
                    
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
                        'msg'=>'Thông tin đã tồn tại',
                        'err'=>0
                    );
                }
            }
            else{
                if (!$order_cost_model->getTireByWhere(array('order_tire'=>$order, 'order_tire_cost_type'=>$order_tire_cost_type, 'vendor'=>$vendor))) {
                    $order_cost_model->createTire($cost_data);

                    $owe_model->createOwe($owe_data);
                    $payable_model->createCosts($payable_data);

                    $last_cost = $order_cost_model->getLastTire()->order_tire_cost_id;

                    $order_tire_model->updateTire(array('order_cost'=>$cost+$data_order->order_cost),array('order_tire_id'=>$order));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$user."|"."add"."|".$last_cost."|order_tire_cost_|"."\n"."\r\n";
                    
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
                        'msg'=>'Thông tin đã tồn tại',
                        'err'=>0
                    );
                }
            }
        }

        header("Content-Type: application/json");
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
        $customer = isset($_GET['customer'])?$_GET['customer']:0;

        $ton=0; $dangve=0; $price="";

        if ($brand>0 && $size>0 && $pattern>0) {
            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tire_going_model = $this->model->get('tiregoingModel');
            $tire_price_discount_model = $this->model->get('tirepricediscountModel');

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

            $sales = $tire_sale_model->queryTire('SELECT * FROM tire_sale WHERE customer = '.$customer.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern.' ORDER BY tire_sale_date DESC LIMIT 1');
            if ($sales) {
                foreach ($sales as $sale) {
                    if ($sale->sell_price_vat > 0) {
                        $price = $sale->sell_price_vat;
                    }
                    else{
                        $price = $sale->sell_price;
                    }
                    
                }
            }
            else{

                $data_q = array(
                    'where' => 'tire_brand ='.$brand.' AND tire_size ='.$size.' AND tire_pattern ='.$pattern.' AND start_date <= '.strtotime(date('d-m-Y')).' AND (end_date IS NULL OR end_date >= '.strtotime(date('d-m-Y')).')  ORDER BY start_date DESC LIMIT 1',
                );
                $prices = $tire_price_discount_model->getAllTire($data_q);
                foreach ($prices as $p) {
                    $price = $p->tire_price;
                }
                
            }
        }
        

        $result = array(
            'data'=>$ton+$dangve,
            'price'=>$price,
            'msg'=>'',
            'err'=>1
        );
        
        header("Content-Type: application/json");
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
            'err'=>1
        );

        header("Content-Type: application/json");
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
            'err'=>1
        );

        header("Content-Type: application/json");
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

        $brands = array();
        $sizes = array();
        $patterns = array();
        
        $orders = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status IS NULL OR import_tire_order_status=1 OR import_tire_order_status=0)','order_by'=>'import_tire_order_date ASC'));
        foreach ($orders as $order) {
            $tire_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$order->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($tire_orders as $tire_order) {
                $hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name] = isset($hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name])?$hangorder[$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name]+$tire_order->tire_number:$tire_order->tire_number;

                $brands[$tire_order->tire_brand_name] = $tire_order->tire_brand;
                $sizes[$tire_order->tire_size_number] = $tire_order->tire_size;
                $patterns[$tire_order->tire_pattern_name] = $tire_order->tire_pattern;
            }
        }
        

        $tire_buys = $tire_buy_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_buy_pattern=tire_pattern_id and tire_buy_size=tire_size_id AND tire_buy_brand=tire_brand_id'));
        foreach ($tire_buys as $buy) {
            $tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name] = isset($tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name])?$tonkho[$buy->tire_brand_name.$buy->tire_size_number.$buy->tire_pattern_name]+$buy->tire_buy_volume:$buy->tire_buy_volume;

            $brands[$buy->tire_brand_name] = $buy->tire_buy_brand;
            $sizes[$buy->tire_size_number] = $buy->tire_buy_size;
            $patterns[$buy->tire_pattern_name] = $buy->tire_buy_pattern;
        }

        $tire_sales = $tire_sale_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_pattern=tire_pattern_id and tire_size=tire_size_id AND tire_brand=tire_brand_id'));
        foreach ($tire_sales as $sale) {
            $tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name] = isset($tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name])?$tonkho[$sale->tire_brand_name.$sale->tire_size_number.$sale->tire_pattern_name]-$sale->volume:(0-$sale->volume);

            $brands[$sale->tire_brand_name] = $sale->tire_brand;
            $sizes[$sale->tire_size_number] = $sale->tire_size;
            $patterns[$sale->tire_pattern_name] = $sale->tire_pattern;
        }

        $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
        foreach ($order_tires as $order) {
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id'));
            foreach ($order_tire_lists as $list) {
                $dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name] = isset($dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name])?$dathang[$list->tire_brand_name.$list->tire_size_number.$list->tire_pattern_name]+$list->tire_number:$list->tire_number;
            }
        }


        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND import_tire_order_status=2','order_by'=>'import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$dangve[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;

                $brands[$going->tire_brand_name] = $going->tire_brand;
                $sizes[$going->tire_size_number] = $going->tire_size;
                $patterns[$going->tire_pattern_name] = $going->tire_pattern;
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
            "Goodride@12.00R20@CM958",
            "Goodride@12.00R20@CB919",
            "Goodride@11R22.5@CR976A",
            "Goodride@11R22.5@AS668",
            "Goodride@11R22.5@CM983",
            "Goodride@12R22.5@CR926",
            "Goodride@12R22.5@CR976A",
            "Goodride@12R22.5@AS668",
            "Goodride@12R22.5@MD738",
            "Goodride@12R22.5@CM985",
            "Goodride@12R22.5@CB919",
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

            $tongton+=intval($ton);
            $tongdangve+=intval($ve);
            $tongdathang+=intval($dat);
            $tongorder+=intval($or);

            $result[] = array(
                'tire_id'=>(string)$i++,
                'tire_brand_name'=>$arr[0],
                'tire_size_number'=>$arr[1],
                'tire_pattern_name'=>$arr[2],
                'tire_brand'=>$brands[$arr[0]],
                'tire_size'=>$sizes[$arr[1]],
                'tire_pattern'=>$patterns[$arr[2]],
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
            'tire_brand'=>'',
            'tire_size'=>'',
            'tire_pattern'=>'',
            'tonkho'=>'Tổng tồn kho: '.$tongton,
            'dathang'=>'Tổng đặt hàng: '.$tongdathang,
            'dangve'=>'Tổng đang về: '.$tongdangve,
            'order'=>'Tổng order: '.$tongorder,
        );

        $result = array(
            'data'=>$result,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
        echo json_encode($result);
        
    }
    public function editcustomer(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $customer = $obj['customer'];
        $user = $obj['user'];
        $type = $obj['type'];
        $customer_name = addslashes(trim($obj['customer_name']));
        $company = addslashes(trim($obj['company']));
        $mst = trim($obj['mst']);
        $address = addslashes(trim($obj['address']));
        $phone = trim($obj['phone']);
        $email = trim($obj['email']);
        $contact = trim($obj['contact']);
        $staff = $obj['staff'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($customer>0 && $user>0) {
            $customer_model = $this->model->get('customerModel');
            $contact_person_model = $this->model->get('contactpersonModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $data = array(
                        
                'customer_name' => $customer_name,
                'customer_address' => $address,
                'customer_phone' => $phone,
                'customer_email' => $email,
                'company_name' => $company,
                'mst' => $mst,
                'customer_contact' => $contact,
                'customer_tire_type' => $type,
                'customer_create_user' => $staff,
                'customer_update_user' => $user,
                'customer_update_time' => time()
            );

            if ($data['mst'] != "" && $data['mst'] > 0) {
                if ($customer_model->getAllCustomerByWhere($customer.' AND mst = '.$mst)) {
                    $result = array(
                        'data'=>null,
                        'msg'=>'Thông tin đã tồn tại',
                        'err'=>0
                    );
                }
            }
            if ($customer_model->getAllCustomerByWhere($customer.' AND customer_name = '.$customer_name)) {
                $result = array(
                    'data'=>null,
                    'msg'=>'Thông tin đã tồn tại',
                    'err'=>0
                );
            }
            else{
                $customers = $customer_model->getCustomer($customer);
                $customer_model->updateCustomer($data,array('customer_id' => $customer));

                $order_tire_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$customer));
                $tire_sale_model->updateTire(array('customer_type'=>$data['customer_tire_type']),array('customer'=>$customer));

                $data_contact_person = array(
                    'contact_person_name' => $contact,
                    'contact_person_phone' => $phone,
                    'contact_person_mobile' => $phone,
                    'contact_person_email' => $email,
                );
                $contact_person_model->updateCustomer($data_contact_person,array('customer'=>$customer,'contact_person_name'=>$customers->customer_contact));

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$user."|"."edit"."|".$customer."|customer|".implode("-",$data)."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);

                $result = array(
                    'data'=>null,
                    'msg'=>'Cập nhật thành công',
                    'err'=>1
                );
            }
            
        }

        header("Content-Type: application/json");
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

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function debit() {
        $this->view->disableLayout();
        
        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        $customer = isset($_GET['customer'])?$_GET['customer']:0;
        $user = isset($_GET['staff'])?$_GET['staff']:0;
        $ketthuc = str_replace('/', '-', $_GET['end']);
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $staff_model = $this->model->get('staffModel');
        $customer_model = $this->model->get('customerModel');
        $order_tire_model = $this->model->get('ordertireModel'); 

        $staff = $user>0?$staff_model->getStaffByWhere(array('account'=>$user))->staff_id:0;

        $data = array(
            'order_by'=>'customer_name',
            'order'=>'ASC',
            'where' => '(customer_id IN (SELECT customer FROM tire_sale) OR customer_id IN (SELECT customer FROM deposit_tire))',
            'fields'=>'customer_id,customer_name'
        );
        if ($staff > 0) {
            $data['where'] = '(customer_id IN (SELECT customer FROM tire_sale WHERE sale  = '.$staff.' ) )';
        }
        if ($customer > 0) {
            $data['where'] .= ' AND customer_id = '.$customer;
        } 
        $customers = $customer_model->getAllCustomer($data);

        $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
        $data = array(
            'where'=>'delivery_date < '.strtotime($ngayketthuc),
            'fields'=>'order_tire_id,total,order_tire_number,username,customer,receivable_id'
        );
        if ($customer > 0) {
            $data['where'] .= ' AND customer_id = '.$customer;
        } 
        if ($staff > 0) {
            $data['where'] .= ' AND sale IN (SELECT account FROM staff WHERE staff_id = '.$staff.') ';
        }
        $orders = $order_tire_model->getAllTire($data,$join);


        $receive_model = $this->model->get('receiveModel');

        $data_customer = array();
        foreach ($orders as $order) {
            $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$order->order_tire_number:$order->order_tire_number;
            $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->total:$order->total;
            $data_customer['sale'][$order->customer] = $order->username;

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime($ngayketthuc),
                'fields'=>'money'
            );
            $receives = $receive_model->getAllCosts($data);
            foreach ($receives as $receive) {
                $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
            }
        }

        $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

        $receivable_model = $this->model->get('receivableModel'); 

        $data = array(
            'where'=>'receivable_date < '.strtotime($ngayketthuc),
            'fields'=>'receivable_id,expect_date,customer,code,money'
            );

        if ($customer > 0) {
            $data['where'] .= ' AND customer_id = '.$customer;
        } 
        

        $receivables = $receivable_model->getAllCosts($data,$join);

        $tire_sale_model = $this->model->get('tiresaleModel'); 
        $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');


        foreach ($receivables as $order) {
            $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."-1 days")));
            $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$order->expect_date)."+1 days")));
            $data = array(
            'where'=>'code = '.$order->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$order->customer,
            'fields'=>'volume,username'
            );
            if ($staff > 0) {
                $data['where'] .= ' AND sale = '.$staff;
            }

            $sales = $tire_sale_model->getAllTire($data,$join);
            foreach ($sales as $sale) {
                $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
            }

            if (!$sales) {
                $data = array(
                'where'=>'code = '.$order->code.' AND customer = '.$order->customer,
                'fields'=>'volume,username'
                );
                if ($staff > 0) {
                    $data['where'] .= ' AND sale = '.$staff;
                }

                $sales = $tire_sale_model->getAllTire($data,$join);
                foreach ($sales as $sale) {
                    $data_customer['number'][$order->customer] = isset($data_customer['number'][$order->customer])?$data_customer['number'][$order->customer]+$sale->volume:$sale->volume;
                    $data_customer['sale'][$order->customer] = isset($data_customer['sale'][$order->customer])?$data_customer['sale'][$order->customer]:$sale->username;
                }
            }

            $data = array(
                'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime($ngayketthuc),
                'fields'=>'money'
            );
            $receives = $receive_model->getAllCosts($data);
            
            if ($sales) {
                $data_customer['money'][$order->customer] = isset($data_customer['money'][$order->customer])?$data_customer['money'][$order->customer]+$order->money:$order->money;
                foreach ($receives as $receive) {
                    $data_customer['pay_money'][$order->customer] = isset($data_customer['pay_money'][$order->customer])?$data_customer['pay_money'][$order->customer]+$receive->money:$receive->money;
                }
                
            }
            
        }

        $deposit_model = $this->model->get('deposittireModel');
        $join = array('table'=>'daily','where'=>'daily = daily_id');
        $data = array(
            'where' => 'daily_date < '.strtotime($ngayketthuc),
            'fields'=>'customer,money_in,money_out,daily'
        );
        $deposits = $deposit_model->getAllDeposit($data,$join);

        foreach ($deposits as $de) {
            $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]+$de->money_in-$de->money_out:$de->money_in-$de->money_out;
            $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime($ngayketthuc));
            foreach ($receives as $re) {
                $data_customer['pay_money'][$de->customer] = isset($data_customer['pay_money'][$de->customer])?$data_customer['pay_money'][$de->customer]-$re->money:(0-$re->money);
            }
        }

        $pay_model = $this->model->get('payableModel');
        $join = array('table'=>'pay','where'=>'pay.payable = payable_id');
        $data = array(
            'where' => 'pay.pay_date < '.strtotime($ngayketthuc),
            'fields'=>'customer,money'
        );
        $pays = $pay_model->getAllCosts($data,$join);

        foreach ($pays as $pay) {
            $data_customer['money'][$pay->customer] = isset($data_customer['money'][$pay->customer])?$data_customer['money'][$pay->customer]+$pay->money:$pay->money;
        }

        $tong = 0;
        $i=0;
        $debits = array();
        foreach ($customers as $order_tire) {
            if (!isset($data_customer['money'][$order_tire->customer_id])) {
                $data_customer['money'][$order_tire->customer_id] = 0;
            }
            if (isset($data_customer['money'][$order_tire->customer_id]) && ($data_customer['money'][$order_tire->customer_id]-$data_customer['pay_money'][$order_tire->customer_id]!=0) ) {
                $debits[$i]['customer_id'] = $order_tire->customer_id;
                $debits[$i]['customer_name'] = $order_tire->customer_name;
                $debits[$i]['money'] = $data_customer['money'][$order_tire->customer_id];
                $debits[$i]['pay_money'] = $data_customer['pay_money'][$order_tire->customer_id];
                $debits[$i]['conlai'] = ($data_customer['money'][$order_tire->customer_id]-$data_customer['pay_money'][$order_tire->customer_id]);
                $tong += ($data_customer['money'][$order_tire->customer_id]-$data_customer['pay_money'][$order_tire->customer_id]);
                $debits[$i]['detail'] = array();
                $j=0;
                if ($customer>0) {
                    $join = array('table'=>'customer, user, receivable','where'=>'customer.customer_id = order_tire.customer AND user_id = sale AND order_tire = order_tire_id');
                    $data = array(
                        'order_by'=>'delivery_date',
                        'order'=>'DESC',
                        'where'=>'order_tire.customer = '.$order_tire->customer_id.' AND delivery_date < '.strtotime($ngayketthuc),
                        'fields'=>'receivable_id,order_number,total,delivery_date,order_tire_id'
                        );

                    $orders = $order_tire_model->getAllTire($data,$join);

                    $join = array('table'=>'customer','where'=>'customer.customer_id = receivable.customer AND trading > 0');

                    $data = array(
                        'order_by'=>'receivable.expect_date',
                        'order'=>'DESC',
                        'where'=>'customer_id = '.$order_tire->customer_id.' AND receivable.expect_date < '.strtotime($ngayketthuc),
                        'fields'=>'receivable_id,code,expect_date,customer,money'
                        );

                    $receivables = $receivable_model->getAllCosts($data,$join);

                    $join = array('table'=>'user, staff','where'=>'user_id = account AND staff_id = sale');
                    
                    $receivable_data = array();
                    foreach ($receivables as $re) {
                        $yesterday = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."-1 days")));
                        $tomorow = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$re->expect_date)."+1 days")));
                        $data = array(
                        'where'=>'code = '.$re->code.' AND tire_sale_date > '.$yesterday.' AND tire_sale_date < '.$tomorow.' AND customer = '.$re->customer,
                        'fields'=>'volume,username'
                        );
                        $sales = $tire_sale_model->getAllTire($data,$join);
                        foreach ($sales as $sale) {
                            $receivable_data[$re->receivable_id]['number'] = isset($receivable_data[$re->receivable_id]['number'])?$receivable_data[$re->receivable_id]['number']+$sale->volume:$sale->volume;
                            $receivable_data[$re->receivable_id]['sale'] = $sale->username;
                        }

                        $data = array(
                            'where' => 'receivable = '.$re->receivable_id.' AND receive_date < '.strtotime($ngayketthuc),
                            'fields'=>'money'
                        );
                        $receives = $receive_model->getAllCosts($data);
                        
                        foreach ($receives as $receive) {
                            $receivable_data[$re->receivable_id]['pay_money'] = isset($receivable_data[$re->receivable_id]['pay_money'])?$receivable_data[$re->receivable_id]['pay_money']+$receive->money:$receive->money;
                        }
                    }
                    foreach ($orders as $order) {
                        $data = array(
                            'where' => 'receivable = '.$order->receivable_id.' AND receive_date < '.strtotime($ngayketthuc),
                        );
                        $receives = $receive_model->getAllCosts($data);
                        
                        foreach ($receives as $receive) {
                            $receivable_data[$order->receivable_id]['pay_money'] = isset($receivable_data[$order->receivable_id]['pay_money'])?$receivable_data[$order->receivable_id]['pay_money']+$receive->money:$receive->money;
                        }
                    }

                    $join = array('table'=>'daily, customer','where'=>'daily = daily_id AND deposit_tire.customer = customer_id');
                    $data = array(
                        'where' => 'deposit_tire.customer = '.$order_tire->customer_id.' AND daily_date < '.strtotime($ngayketthuc),
                        'fields'=>'daily,deposit_tire_id,money_in,money_out,comment,daily_date'
                    );
                    $deposits = $deposit_model->getAllDeposit($data,$join);

                    $deposit_data = array();
                    foreach ($deposits as $de) {
                        $receives = $receive_model->queryCosts('SELECT receive_id, receive.money, receive_comment, receivable.code FROM receive, receivable WHERE receivable=receivable_id AND receive.additional = '.$de->daily.' AND receivable_date < '.strtotime($ngayketthuc));
                        foreach ($receives as $re) {
                            $deposit_data[$de->deposit_tire_id]['pay_money'] = isset($deposit_data[$de->deposit_tire_id]['pay_money'])?$deposit_data[$de->deposit_tire_id]['pay_money']+$re->money:$re->money;
                        }
                    }

                    $join = array('table'=>'pay, customer','where'=>'pay.payable = payable_id AND payable.customer = customer_id');
                    $data = array(
                        'where' => 'payable.customer = '.$order_tire->customer_id.' AND pay.pay_date < '.strtotime($ngayketthuc),
                    );
                    $pays = $pay_model->getAllCosts($data,$join);


                    foreach ($orders as $order_list) {
                        $pay_money = isset($receivable_data[$order_list->receivable_id]['pay_money'])?$receivable_data[$order_list->receivable_id]['pay_money']:0;
                        if ($order_list->total-$pay_money != 0) {
                            $debits[$i]['detail'][$j]['stt'] = $j;
                            $debits[$i]['detail'][$j]['code'] = $order_list->order_number;
                            $debits[$i]['detail'][$j]['order'] = $order_list->order_tire_id;
                            $debits[$i]['detail'][$j]['date'] = $order_list->delivery_date;
                            $debits[$i]['detail'][$j]['money'] = $order_list->total;
                            $debits[$i]['detail'][$j]['pay_money'] = $pay_money;
                            $debits[$i]['detail'][$j]['conlai'] = $order_list->total-$pay_money;
                            $j++;
                        }

                    }

                    foreach ($receivables as $order_list) {
                        $pay_money = isset($receivable_data[$order_list->receivable_id]['pay_money'])?$receivable_data[$order_list->receivable_id]['pay_money']:0;
                        if ($order_list->money-$pay_money != 0) {
                            $debits[$i]['detail'][$j]['stt'] = $j;
                            $debits[$i]['detail'][$j]['code'] = $order_list->code;
                            $debits[$i]['detail'][$j]['order'] = "";
                            $debits[$i]['detail'][$j]['date'] = $order_list->expect_date;
                            $debits[$i]['detail'][$j]['money'] = $order_list->money;
                            $debits[$i]['detail'][$j]['pay_money'] = $pay_money;
                            $debits[$i]['detail'][$j]['conlai'] = $order_list->money-$pay_money;
                            $j++;
                        }

                    }

                    foreach ($deposits as $order_list) {
                        $pay_money = isset($deposit_data[$order_list->deposit_tire_id]['pay_money'])?$deposit_data[$order_list->deposit_tire_id]['pay_money']:0;
                        if ($order_list->money_in-$pay_money-$order_list->money_out != 0) {
                            $debits[$i]['detail'][$j]['stt'] = $j;
                            $debits[$i]['detail'][$j]['code'] = $order_list->comment;
                            $debits[$i]['detail'][$j]['order'] = "";
                            $debits[$i]['detail'][$j]['date'] = $order_list->daily_date;
                            $debits[$i]['detail'][$j]['money'] = $order_list->money_out;
                            $debits[$i]['detail'][$j]['pay_money'] = $order_list->money_in-$pay_money;
                            $debits[$i]['detail'][$j]['conlai'] = $order_list->money_out-$order_list->money_in-$pay_money;
                            $j++;
                        }

                    }

                    foreach ($pays as $order_list) {
                        $debits[$i]['detail'][$j]['stt'] = $j;
                        $debits[$i]['detail'][$j]['code'] = $order_list->pay_comment;
                        $debits[$i]['detail'][$j]['order'] = "";
                        $debits[$i]['detail'][$j]['date'] = $order_list->pay_date;
                        $debits[$i]['detail'][$j]['money'] = $order_list->money;
                        $debits[$i]['detail'][$j]['pay_money'] = 0;
                        $debits[$i]['detail'][$j]['conlai'] = $order_list->money;
                        $j++;

                    }
                }
                $i++;
            }
        }

        $result = array(
            'data'=>$debits,
            'debit'=>$tong,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function report() {
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
        $tire_sale_model = $this->model->get('tiresaleModel');
        $staff_model = $this->model->get('staffModel');

        $data = array(
            'where' => 'tire_sale_date < '.strtotime($batdau).' GROUP BY customer',
        );
        $sale_olds = $tire_sale_model->getAllTire($data);

        $old = array();
        foreach ($sale_olds as $sale) {
            if (!in_array($sale->customer,$old)) {
                $old[] = $sale->customer;
            }
        }

        $qr = 'SELECT tire_sale_id, tire_brand_name, tire_size_number, tire_pattern_name, sum(volume) as tong FROM `tire_sale` LEFT JOIN tire_brand ON tire_brand=tire_brand_id LEFT JOIN tire_size ON tire_size=tire_size_id LEFT JOIN tire_pattern ON tire_pattern=tire_pattern_id  WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc).' GROUP BY tire_brand, tire_size, tire_pattern ORDER BY tong DESC LIMIT 5';

       $sales = $tire_sale_model->queryTire($qr);
       $sale_arr = array();
       foreach ($sales as $sale) {
           $sale_arr[] = $sale;
       }

        $c = array();
        $info_staff = array();

        $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc).')'));


        $doanhthu = 0;
        $sanluong = 0;
        $khachhang = 0;
        $donhang = 0;

        foreach ($orders as $tire) {
            $doanhthu += $tire->total;
            $sanluong += $tire->order_tire_number;
            $donhang++;
            

            $info_staff['sl'][$tire->sale] = isset($info_staff['sl'][$tire->sale])?$info_staff['sl'][$tire->sale]+$tire->order_tire_number:$tire->order_tire_number;
            $info_staff['dt'][$tire->sale] = isset($info_staff['dt'][$tire->sale])?$info_staff['dt'][$tire->sale]+$tire->total:$tire->total;
            $info_staff['dh'][$tire->sale] = isset($info_staff['dh'][$tire->sale])?$info_staff['dh'][$tire->sale]+1:1;

            if (isset($c[$tire->sale])) {
                if(!in_array($tire->customer,$c[$tire->sale])){
                    $info_staff['kh'][$tire->sale] = isset($info_staff['kh'][$tire->sale])?$info_staff['kh'][$tire->sale]+1:1;
                    if ($tire->customer_type==1) {
                        $info_staff['dl'][$tire->sale] = isset($info_staff['dl'][$tire->sale])?$info_staff['dl'][$tire->sale]+1:1;
                    }
                }
            }
            else{
                $info_staff['kh'][$tire->sale] = 1;
            }
            

            $c[$tire->sale][] = $tire->customer;
            if (!in_array($tire->customer,$old)) {
                $info_staff['khmoi'][$tire->sale] = isset($info_staff['khmoi'][$tire->sale])?$info_staff['khmoi'][$tire->sale]+1:1;
                if ($tire->customer_type==1) {
                    $info_staff['dlmoi'][$tire->sale] = isset($info_staff['dlmoi'][$tire->sale])?$info_staff['dlmoi'][$tire->sale]+1:1;
                }
                $khachhang++;
                $old[] = $tire->customer;
            }

        }

        $join = array('table'=>'user','where'=>'account=user_id','fields'=>'staff_id,staff_name,account,user_id');
        $data = array(
            'where' => 'staff_id IN (SELECT sale FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc).') AND ( (start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) ) )',
        );
        $staff_sales = $staff_model->getAllStaff($data,$join);

        $staffs = array();
        $i=0;
        foreach ($staff_sales as $staff) {
            $khmoi = isset($info_staff['khmoi'][$staff->user_id])?$info_staff['khmoi'][$staff->user_id]:null;

            $staffs[$i]['staff_id'] = $staff->staff_id;
            $staffs[$i]['staff_name'] = $staff->staff_name;
            $staffs[$i]['donhang'] = isset($info_staff['dh'][$staff->user_id])?$info_staff['dh'][$staff->user_id]:null;
            $staffs[$i]['sanluong'] = isset($info_staff['sl'][$staff->user_id])?$info_staff['sl'][$staff->user_id]:null;
            $staffs[$i]['doanhthu'] = isset($info_staff['dt'][$staff->user_id])?$info_staff['dt'][$staff->user_id]:null;
            $staffs[$i]['khmoi'] = $khmoi;
            $staffs[$i]['khcu'] = isset($info_staff['kh'][$staff->user_id])?$info_staff['kh'][$staff->user_id]-$khmoi:0;
            $staffs[$i]['dl'] = isset($info_staff['dl'][$staff->user_id])?$info_staff['dl'][$staff->user_id]:(isset($info_staff['dlmoi'][$staff->user_id])?$info_staff['dlmoi'][$staff->user_id]:0);
            $i++;
        }
        
        usort($staffs, array($this,'cmp'));

        $arr = array(
            'doanhthu'=>$doanhthu,
            'sanluong'=>$sanluong,
            'khachhang'=>$khachhang,
            'donhang'=>$donhang,
            'staff'=>$staffs,
            'sale'=>$sale_arr
        );

        $result = array(
            'data'=>$arr,
            'msg'=>'',
            'err'=>1
        );

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    function cmp($a, $b){
        if ($a == $b)
            return 0;
        return ($a['sanluong'] > $b['sanluong']) ? -1 : 1;
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
        
        header("Content-Type: application/json");
        echo json_encode($result);
    }

    public function quotationview(){
        
    }    
    public function sendquotation(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $user = $obj['user'];
        $value = $obj['value'];
        $email = $obj['email'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($user>0) {
            $this->writeHTML($value,$user);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function createquotation(){
        $this->view->disableLayout();
        
        $json = file_get_contents('php://input');
        $obj = json_decode($json,true);

        $user = $obj['user'];
        $value = $obj['value'];

        $result = array(
            'data'=>null,
            'msg'=>'',
            'err'=>null
        );

        if ($user>0) {
            $this->writeHTML($value,$user);
        }

        header("Content-Type: application/json");
        echo json_encode($result);
    }
    public function writeHTML($data,$user){
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getStaffByWhere(array('account'=>$user));

        $this->view->data['tires'] = $data;
        $this->view->data['staffs'] = $staffs;

        $chuky = '
        <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
            <tbody>
                <tr>
                    <td style="width:131.25pt;padding:0in 0in 0in 0in" valign="top" width="175">
                        <p class="MsoNormal">
        <!--[if gte vml 1]><v:shapetype  id="_x0000_t75" coordsize="21600,21600" o:spt="75" o:preferrelative="t" path="m@4@5l@4@11@9@11@9@5xe" filled="f" stroked="f"><v:stroke joinstyle="miter"></v:stroke><v:formulas><v:f eqn="if lineDrawn pixelLineWidth 0"></v:f><v:f eqn="sum @0 1 0"></v:f><v:f eqn="sum 0 0 @1"></v:f><v:f eqn="prod @2 1 2"></v:f><v:f eqn="prod @3 21600 pixelWidth"></v:f><v:f eqn="prod @3 21600 pixelHeight"></v:f><v:f eqn="sum @0 0 1"></v:f><v:f eqn="prod @6 1 2"></v:f><v:f eqn="prod @7 21600 pixelWidth"></v:f><v:f eqn="sum @8 21600 0"></v:f><v:f eqn="prod @7 21600 pixelHeight"></v:f><v:f eqn="sum @10 21600 0"></v:f></v:formulas><v:path o:extrusionok="f" gradientshapeok="t" o:connecttype="rect"></v:path><o:lock v:ext="edit" aspectratio="t"></o:lock></v:shapetype><v:shape id="Picture_x0020_1" o:spid="_x0000_i1027" type="#_x0000_t75" style="width:151.5pt;height:45.75pt;visibility:visible;mso-wrap-style:square"><v:imagedata src=http://viet-trade.org/public/images/1.png o:title=""></v:imagedata></v:shape><![endif]--><!--[if !vml]-->                   <span new="" style="font-size: 12pt; font-family: " times=""><img height="61" src="http://viet-trade.org/public/images/1.png" v:shapes="Picture_x0020_1" width="202" /><!--[endif]--><o:p></o:p></span></p>
                    </td>
                    <td style="padding:0in 0in 0in 0in">
                        <p class="MsoNormal">
                            <b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Viet Trade Company Limited</span></b><span style="font-size: 10pt; font-family: Arial, sans-serif;">&nbsp;<br />
                            </span><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">No.29, 51 Highway, Phuoc Tan ward, Bien Hoa city, Dong Nai province, Vietnam.<br />
                            Tel: +84 (251) 3 937 607 / 747 - Fax: +84 (251) 3 937 677 - Hotline: +84 (28) 3 500 9000</span><span style="font-size: 10pt; font-family: Arial, sans-serif;"><br />
                            </span><b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">Website:</span></b><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:#333333;mso-no-proof:yes">&nbsp;</span><a href="www.viet-trade.org"><span style="font-size:10.0pt;font-family:&quot;Arial&quot;,sans-serif;mso-fareast-font-family:&quot;Times New Roman&quot;;color:blue">www.viet-trade.org</span></a><span new="" style="font-size: 12pt; font-family: " times=""><o:p></o:p></span></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p>
            &nbsp;</p>
        <table border="0" cellpadding="0" cellspacing="0" class="MsoNormalTable" style="width: 100%; border-collapse: collapse; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;" width="100%">
        </table>
        <div align="center" class="MsoNormal" style="text-align:center">
            <hr align="center" noshade="noshade" size="1" style="color:#CCCCCC" width="100%" />
        </div>
        <p style="color: rgb(0, 0, 0); font-family: Verdana, arial, Helvetica, sans-serif;">
            <b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">&ldquo;NH&Agrave;&nbsp;NHẬP KHẨU&nbsp;V&Agrave;&nbsp;PH&Acirc;N PHỐI TRỰC TIẾP&nbsp;</span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:red;mso-no-proof:yes">LỐP XE BỐ KẼM </span></b><b style="color: rgb(34, 34, 34); font-family: Arial, Verdana, sans-serif;"><span style="font-family:Consolas;mso-fareast-font-family:Calibri;mso-bidi-font-family:&quot;Times New Roman&quot;;color:black;mso-no-proof:yes">CAO CẤP&nbsp;(Gi&aacute; rẻ nhất thị trường)&rdquo;</span></b></p>
        
        ';

        $this->view->show('api/quotation');
    }
    
    public function send_mail_auto($email,$content,$subject,$sign,$user,$pass,$host,$images=array(),$pdfs=array()){
        
        require "lib/class.phpmailer.php";
        require("lib/Classes/tcpdf/tcpdf.php");

            $arr = $email;
            $noidung = stripslashes(trim($content));
            $chude = trim($subject);
            $usr = trim($user);
            $pas = trim($pass);
            $hostname = trim($host);
            $chuky = stripslashes(trim($sign));
            $hinhanh = $images;

            $err = array();

            
                // Khai báo tạo PHPMailer
                $mail = new PHPMailer();
                //Khai báo gửi mail bằng SMTP
                $mail->IsSMTP();
                //Tắt mở kiểm tra lỗi trả về, chấp nhận các giá trị 0 1 2
                // 0 = off không thông báo bất kì gì, tốt nhất nên dùng khi đã hoàn thành.
                // 1 = Thông báo lỗi ở client
                // 2 = Thông báo lỗi cả client và lỗi ở server
                $mail->SMTPDebug  = 0;
                 
                $mail->Debugoutput = "html"; // Lỗi trả về hiển thị với cấu trúc HTML
                $mail->Host       = $hostname; //host smtp để gửi mail
                $mail->Port       = 587; // cổng để gửi mail
                $mail->SMTPSecure = "tls"; //Phương thức mã hóa thư - ssl hoặc tls
                $mail->SMTPAuth   = true; //Xác thực SMTP
                $mail->CharSet = 'UTF-8';
                $mail->Username   = $usr; // Tên đăng nhập tài khoản Gmail
                $mail->Password   = $pas; //Mật khẩu của gmail
                $mail->SetFrom($usr, "Việt Trade"); // Thông tin người gửi
                //$mail->AddReplyTo("sale@cmglogistics.com.vn","Sale CMG");// Ấn định email sẽ nhận khi người dùng reply lại.

                $mail->AddAddress($arr, $arr);//Email của người nhận
                $mail->Subject = $chude; //Tiêu đề của thư
                $mail->IsHTML(true); // send as HTML   

                foreach ($hinhanh as $key => $value) {
                    $mail->AddEmbeddedImage($hinhanh['link'], $hinhanh['ten']);
                }

                foreach ($pdfs as $key => $value) {
                    $filename = $pdfs['name'].".pdf";
                    $mail->AddAttachment('public/files/'.$filename);//Tập tin cần attach
                    
                    //$pdf_content = file_get_contents('public/files/'.$filename);
                    //$mail->AddStringAttachment($pdf_content, $filename, "base64", "application/pdf");  // note second item is name of emailed pdf
                }
                

                $mail->MsgHTML($noidung.$chuky); //Nội dung của bức thư.
                // $mail->MsgHTML(file_get_contents("email-template.html"), dirname(__FILE__));
                // Gửi thư với tập tin html

                $mail->AltBody = $chude;//Nội dung rút gọn hiển thị bên ngoài thư mục thư.
                //$mail->AddAttachment("images/attact-tui.gif");//Tập tin cần attach
                // For most clients expecting the Priority header:
                // 1 = High, 2 = Medium, 3 = Low
                $mail->Priority = 1;
                // MS Outlook custom header
                // May set to "Urgent" or "Highest" rather than "High"
                $mail->AddCustomHeader("X-MSMail-Priority: High");
                // Not sure if Priority will also set the Importance header:
                $mail->AddCustomHeader("Importance: High"); 

                if(!$mail->Send()){
                    $err[] = array(
                        'email' => $arr,
                        'err' => 1,
                    );
                }
                else{
                    $err[] = array(
                        'email' => $arr,
                        'err' => 0,
                    );
                }
                //Tiến hành gửi email và kiểm tra lỗi
                
        
    }
}
?>