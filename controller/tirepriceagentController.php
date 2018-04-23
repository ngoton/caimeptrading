<?php

Class tirepriceagentController Extends baseController {

    public function index() {

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Bảng giá lốp xe';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'customer_name ASC, tire_brand ASC, start_date';

            $order = $this->registry->router->order ? $this->registry->router->order : 'ASC, tire_price_agent_id ASC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = 50;

        }

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name','order'=>'ASC'));
        $this->view->data['customers'] = $customers;

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name','order'=>'ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number','order'=>'ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name','order'=>'ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;


        $join = array('table'=>'tire_brand, tire_size, tire_pattern, customer','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND customer=customer_id');



        $tire_price_agent_model = $this->model->get('tirepriceagentModel');

        $sonews = $limit;

        $x = ($page-1) * $sonews;

        $pagination_stages = 2;

        

        $tongsodong = count($tire_price_agent_model->getAllTire(null,$join));

        $tongsotrang = ceil($tongsodong / $sonews);

        



        $this->view->data['page'] = $page;

        $this->view->data['order_by'] = $order_by;

        $this->view->data['order'] = $order;

        $this->view->data['keyword'] = $keyword;

        $this->view->data['limit'] = $limit;

        $this->view->data['pagination_stages'] = $pagination_stages;

        $this->view->data['tongsotrang'] = $tongsotrang;

        $this->view->data['sonews'] = $sonews;



        $data = array(

            'order_by'=>$order_by,

            'order'=>$order,

            'limit'=>$x.','.$sonews,

            );

        

        if ($keyword != '') {

            $search = '( tire_brand_name LIKE "%'.$keyword.'%" 
                OR tire_size_number LIKE "%'.$keyword.'%" 
                OR tire_pattern_name LIKE "%'.$keyword.'%"
                OR customer_name LIKE "%'.$keyword.'%"
                )';

            $data['where'] = $search;

        }

        

        

        

        $this->view->data['tires'] = $tire_price_agent_model->getAllTire($data,$join);



        $this->view->data['lastID'] = isset($tire_price_agent_model->getLastTire()->tire_price_agent_id)?$tire_price_agent_model->getLastTire()->tire_price_agent_id:0;

        

        $this->view->show('tirepriceagent/index');

    }



    public function add(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }


        if (isset($_POST['yes'])) {

            $tire_price_agent_model = $this->model->get('tirepriceagentModel');
            $data = array(

                        'start_date' => strtotime(trim($_POST['start_date'])),

                        'end_date' => strtotime(trim($_POST['end_date'])),

                        'tire_brand' => trim($_POST['tire_brand']),

                        'tire_size' => trim($_POST['tire_size']),

                        'tire_pattern' => trim($_POST['tire_pattern']),

                        'tire_price' => trim(str_replace(',', '', $_POST['tire_price'])),

                        'customer' => trim($_POST['customer']),

                        );

            if ($_POST['yes'] != "") {

                    $tire_d = $tire_price_agent_model->getTire($_POST['yes']);

                    $tire1 = $tire_price_agent_model->getTireByWhere(array('customer'=>$tire_d->customer,'tire_brand'=>$tire_d->tire_brand,'tire_size'=>$tire_d->tire_size,'tire_pattern'=>$tire_d->tire_pattern,'end_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$tire_d->start_date).' -1 day'))))));
                    $tire2 = $tire_price_agent_model->getTireByWhere(array('customer'=>$tire_d->customer,'tire_brand'=>$tire_d->tire_brand,'tire_size'=>$tire_d->tire_size,'tire_pattern'=>$tire_d->tire_pattern,'start_date'=>(strtotime(date('d-m-Y',strtotime(date('d-m-Y',$tire_d->end_date).' +1 day'))))));
                    if($tire1)
                        $tire_price_agent_model->updateTire(array('customer'=>$tire_d->customer,'tire_brand'=>$tire_d->tire_brand,'tire_size'=>$tire_d->tire_size,'tire_pattern'=>$tire_d->tire_pattern,'end_date'=>(strtotime(date('d-m-Y',strtotime($_POST['start_date'].' -1 day'))))),array('tire_price_agent_id' => $tire1->tire_price_agent_id));
                    if($tire2)
                        $tire_price_agent_model->updateTire(array('customer'=>$tire_d->customer,'tire_brand'=>$tire_d->tire_brand,'tire_size'=>$tire_d->tire_size,'tire_pattern'=>$tire_d->tire_pattern,'start_date'=>(strtotime(date('d-m-Y',strtotime($_POST['end_date'].' +1 day'))))),array('tire_price_agent_id' => $tire2->tire_price_agent_id));


                    $tire_price_agent_model->updateTire($data,array('tire_price_agent_id' => trim($_POST['yes'])));

                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_price_agent|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                    

            }

            else{

                //$data['driver_create_user'] = $_SESSION['userid_logined'];

                //$data['staff'] = $_POST['staff'];

                //var_dump($data);

                if ($tire_price_agent_model->getTireByWhere(array('tire_brand'=>$data['tire_brand'],'tire_size'=>$data['tire_size'],'tire_pattern'=>$data['tire_pattern'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],'customer'=>$data['customer']))) {

                    echo "Thông tin này đã tồn tại";

                    return false;

                }

                else{

                    $dm1 = $tire_price_agent_model->queryTire('SELECT * FROM tire_price_agent WHERE customer='.$data['customer'].' AND tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND start_date <= '.$data['start_date'].' AND end_date <= '.$data['end_date'].' AND end_date >= '.$data['start_date'].' ORDER BY end_date ASC LIMIT 1');
                    $dm2 = $tire_price_agent_model->queryTire('SELECT * FROM tire_price_agent WHERE customer='.$data['customer'].' AND tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND end_date >= '.$data['end_date'].' AND start_date >= '.$data['start_date'].' AND start_date <= '.$data['end_date'].' ORDER BY end_date ASC LIMIT 1');
                    $dm3 = $tire_price_agent_model->queryTire('SELECT * FROM tire_price_agent WHERE customer='.$data['customer'].' AND tire_brand='.$data['tire_brand'].' AND tire_size='.$data['tire_size'].' AND tire_pattern='.$data['tire_pattern'].' AND start_date <= '.$data['start_date'].' AND end_date >= '.$data['end_date'].' ORDER BY end_date ASC LIMIT 1');

                    if ($dm3) {
                            foreach ($dm3 as $row) {
                                $d = array(
                                    'end_date' => strtotime(date('d-m-Y',strtotime($_POST['start_date'].' -1 day'))),
                                    );
                                $tire_price_agent_model->updateTire($d,array('tire_price_agent_id'=>$row->tire_price_agent_id));

                                $c = array(
                                    'customer' => $row->customer,
                                    'tire_brand' => $row->tire_brand,
                                    'tire_size' => $row->tire_size,
                                    'tire_pattern' => $row->tire_pattern,
                                    'tire_price' => $row->tire_price,
                                    'start_date' => strtotime(date('d-m-Y',strtotime($_POST['end_date'].' +1 day'))),
                                    'end_date' => $row->end_date,
                                    );
                                $tire_price_agent_model->createTire($c);

                            }

                            

                            
                            $tire_price_agent_model->createTire($data);

                        }
                        else if ($dm1 || $dm2) {
                            if($dm1){
                                foreach ($dm1 as $row) {
                                    $d = array(
                                        'end_date' => strtotime(date('d-m-Y',strtotime($_POST['start_date'].' -1 day'))),
                                        );
                                    $tire_price_agent_model->updateTire($d,array('tire_price_agent_id'=>$row->tire_price_agent_id));

                                    
                                }
                            }
                            if($dm2){
                                foreach ($dm2 as $row) {
                                    $d = array(
                                        'start_date' => strtotime(date('d-m-Y',strtotime($_POST['end_date'].' +1 day'))),
                                        );
                                    $tire_price_agent_model->updateTire($d,array('tire_price_agent_id'=>$row->tire_price_agent_id));


                                }
                            }


                            
                            $tire_price_agent_model->createTire($data);

                        
                    }
                    else{
                        $tire_price_agent_model->createTire($data);

                    }

                    echo "Thêm thành công";


                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_price_agent_model->getLastTire()->tire_price_agent_id."|tire_price_agent|".implode("-",$data)."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                

            }

                    

        }

    }



    

    



    public function delete(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $tire_price_agent_model = $this->model->get('tirepriceagentModel');

            if (isset($_POST['xoa'])) {

                $data = explode(',', $_POST['xoa']);

                foreach ($data as $data) {
                    $tire_price_agent_model->deleteTire($data);
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_price_agent|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);

                }

                return true;

            }

            else{

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 

                        $filename = "action_logs.txt";

                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|driver|"."\n"."\r\n";

                        

                        $fh = fopen($filename, "a") or die("Could not open log file.");

                        fwrite($fh, $text) or die("Could not write file!");

                        fclose($fh);



                return $tire_price_agent_model->deleteTire($_POST['data']);

            }

            

        }

    }


    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 9) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $tirepriceagent = $this->model->get('tirepriceagentModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $customer_model = $this->model->get('customerModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();
            $cell = $objWorksheet->getCellByColumnAndRow(0, 1);
            $brand = $cell->getCalculatedValue(); 
            if (!$tire_brand_model->getTireByWhere(array('tire_brand_name'=>trim($brand)))) {
                $data_brand = array(
                    'tire_brand_name'=>trim($brand),
                );
                $tire_brand_model->createTire($data_brand);
                $tire_brand = $tire_brand_model->getLastTire();
            }
            else{
                $tire_brand = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>trim($brand)));
            }

            $cell_cus = $objWorksheet->getCellByColumnAndRow(11, 1);
            $cus = $cell_cus->getCalculatedValue(); 
            $customer = $customer_model->getCustomerByWhere(array('customer_name'=>trim($cus)));
            

            $cell_ngay = $objWorksheet->getCellByColumnAndRow(10, 1);
            $ngay = $cell_ngay->getCalculatedValue();
            $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);

            $ngaytruoc = strtotime(date('d-m-Y',strtotime(date('d-m-Y',$ngaythang).' -1 day')));

                for ($row = 3; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        $val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[0] != null  ) {
                        if (!$tire_size_model->getTireByWhere(array('tire_size_number'=>trim($val[0])))) {
                            $data_brand = array(
                                'tire_size_number'=>trim($val[0]),
                            );
                            $tire_size_model->createTire($data_brand);
                            $tire_size = $tire_size_model->getLastTire();
                        }
                        else{
                            $tire_size = $tire_size_model->getTireByWhere(array('tire_size_number'=>trim($val[0])));
                        }
                        
                        if (!$tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>trim($val[1])))) {
                            $data_brand = array(
                                'tire_pattern_name'=>trim($val[1]),
                            );
                            $tire_pattern_model->createTire($data_brand);
                            $tire_pattern = $tire_pattern_model->getLastTire();
                        }
                        else{
                            $tire_pattern = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>trim($val[1])));
                        }

                            if (!$tirepriceagent->getTireByWhere(array('tire_brand'=>$tire_brand->tire_brand_id,'tire_size'=>$tire_size->tire_size_id,'tire_pattern'=>$tire_pattern->tire_pattern_id,'start_date'=>$ngaythang,'customer' =>  $customer->customer_id))) {
                                $tirepriceagent->queryTire('UPDATE tire_price_agent SET end_date = '.$ngaytruoc.' WHERE (end_date IS NULL OR end_date = 0) AND tire_brand='.$tire_brand->tire_brand_id.' AND tire_size='.$tire_size->tire_size_id.' AND tire_pattern='.$tire_pattern->tire_pattern_id.' AND customer='. $customer->customer_id);

                                $data = array(
                                    'tire_brand'=>$tire_brand->tire_brand_id,
                                    'tire_size'=>$tire_size->tire_size_id,
                                    'tire_pattern'=>$tire_pattern->tire_pattern_id,
                                    'start_date' => $ngaythang,
                                    'tire_price' => $val[3],
                                    'customer' =>  $customer->customer_id,
                                );
                                $tirepriceagent->createTire($data);
                            }
                            else if ($tirepriceagent->getTireByWhere(array('tire_brand'=>$tire_brand->tire_brand_id,'tire_size'=>$tire_size->tire_size_id,'tire_pattern'=>$tire_pattern->tire_pattern_id,'start_date'=>$ngaythang,'customer' =>  $customer->customer_id))) {
                                $id_quotation = $tirepriceagent->getTireByWhere(array('tire_brand'=>$tire_brand->tire_brand_id,'tire_size'=>$tire_size->tire_size_id,'tire_pattern'=>$tire_pattern->tire_pattern_id,'start_date'=>$ngaythang,'customer' =>  $customer->customer_id))->tire_price_agent_id;
                                $data = array(
                                    'tire_brand'=>$tire_brand->tire_brand_id,
                                    'tire_size'=>$tire_size->tire_size_id,
                                    'tire_pattern'=>$tire_pattern->tire_pattern_id,
                                    'tire_price' => $val[3],
                                    'customer' =>  $customer->customer_id,
                                );
                                $tirepriceagent->updateTire($data,array('tire_price_agent_id'=>$id_quotation));
                            }


                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('tirepriceagent');
        }
        $this->view->show('tirepriceagent/import');

    }
    


}

?>