<?php
Class importcontractController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Hàng order';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $tha = isset($_POST['tha']) ? $_POST['tha'] : null;
            $na = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'import_contract_code';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-01-'.date('Y');
            $ketthuc = date('t-m-Y');
            $nv = 1;
            $tha = date('m');
            $na = date('Y');
        }

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $import_contract_model = $this->model->get('importcontractModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => 'import_contract_date >= '.strtotime($batdau).' AND import_contract_date < '.strtotime($ngayketthuc),
        );
        
        $tongsodong = count($import_contract_model->getAllImport($data,null));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['nv'] = $nv;
        $this->view->data['tha'] = $tha;
        $this->view->data['na'] = $na;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'import_contract_date >= '.strtotime($batdau).' AND import_contract_date < '.strtotime($ngayketthuc),
            );
        
      
        if ($keyword != '') {
            $search = '( import_contract_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $import_contracts = $import_contract_model->getAllImport($data,null);

        
        $this->view->data['import_contracts'] = $import_contracts;

       
        $this->view->data['lastID'] = isset($import_contract_model->getLastImport()->import_contract_code)?$import_contract_model->getLastImport()->import_contract_code:0;


        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('importcontract/index');
    }
    
   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $import_contract_model = $this->model->get('importcontractModel');
            $import_contract_list_model = $this->model->get('importcontractlistModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            
            $data = array(
                        
                'import_contract_code' => trim($_POST['import_contract_code']),
                'import_contract_comment' => trim($_POST['import_contract_comment']),
                'import_contract_date' => strtotime(str_replace('/', '-', $_POST['import_contract_date'])),
                
            );
            
            

            if ($_POST['yes'] != "") {
                    $import_orders = $import_contract_model->getImport($_POST['yes']);

                    $import_contract_model->updateImport($data,array('import_contract_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    $id_order = $_POST['yes'];

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|import_contract|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                
                    $import_contract_model->createImport($data);

                    
                    echo "Thêm thành công";

                    $id_order = $import_contract_model->getLastImport()->import_contract_id;

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_order."|import_contract|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }

            $tongsl = 0;

            $import_list = $_POST['import_list'];
            $arr_item = "";
            foreach ($import_list as $v) {
                $id_list = $v['tire_list_id'];
                $data_list = array(
                    'tire_number'=> $v['tire_number'],
                    'import_contract'=> $id_order,
                );

                $brand = trim($v['tire_brand']);
                $size = trim($v['tire_size']);
                $pattern = trim($v['tire_pattern']);

                if ($brand != "") {
                    if ($tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))) {
                        $data_list['tire_brand'] = $tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))->tire_brand_id;
                    }
                    else{
                        $tire_brand_model->createTire(array('tire_brand_name' => $brand));
                        $tire_brand_id = $tire_brand_model->getLastTire()->tire_brand_id;
                        $data_list['tire_brand'] = $tire_brand_id;
                    }
                    
                }
                if ($size != "") {
                    if ($tire_size_model->getTireByWhere(array('tire_size_number' => $size))) {
                        $data_list['tire_size'] = $tire_size_model->getTireByWhere(array('tire_size_number' => $size))->tire_size_id;
                    }
                    else{
                        $tire_size_model->createTire(array('tire_size_number' => $size));
                        $tire_size_id = $tire_size_model->getLastTire()->tire_size_id;
                        $data_list['tire_size'] = $tire_size_id;
                    }
                    
                }
                if ($pattern != "") {
                    if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))) {
                        $data_list['tire_pattern'] = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))->tire_pattern_id;
                    }
                    else{
                        $tire_pattern_model->createTire(array('tire_pattern_name' => $pattern));
                        $tire_pattern_id = $tire_pattern_model->getLastTire()->tire_pattern_id;
                        $data_list['tire_pattern'] = $tire_pattern_id;
                    }
                    
                }

                if ($data_list['tire_brand']>0 && $data_list['tire_size']>0 && $data_list['tire_pattern']>0 && $data_list['tire_number']>0) {
                    if ($id_list>0) {
                        $import_contract_list_model->updateImport($data_list,array('import_contract_list_id'=>$id_list));
                        
 
                    }
                    else{
                        $import_contract_list_model->createImport($data_list);
                        $id_list = $import_contract_list_model->getLastImport()->import_contract_list_id;

                    }

                    if ($arr_item=="") {
                        $arr_item .= $id_list;
                    }
                    else{
                        $arr_item .= ','.$id_list;
                    }

                    $tongsl += $data_list['tire_number'];
                }

                
            }

            $import_contract_model->updateImport(array('import_contract_number'=>$tongsl),array('import_contract_id' => $id_order));


            $item_olds = $import_contract_list_model->queryImport('SELECT * FROM import_contract_list WHERE import_contract='.$id_order.' AND import_contract_list_id NOT IN ('.$arr_item.')');
            foreach ($item_olds as $item_old) {
                $import_contract_list_model->queryImport('DELETE FROM import_contract_list WHERE import_contract_list_id='.$item_old->import_contract_list_id);
            }

            

                    
        }
    }

    public function delall(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $import_contract_list_model = $this->model->get('importcontractlistModel');
        $import_contract_list_model->queryImport('DELETE FROM import_contract_list WHERE import_contract IS NULL OR import_contract=0');
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $import_contract_model = $this->model->get('importcontractModel');
            $import_contract_list_model = $this->model->get('importcontractlistModel');
            
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                    
                    $import_contract_list_model->queryImport('DELETE FROM import_contract_list WHERE import_contract='.$data);
                       $import_contract_model->deleteImport($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|import_contract|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                $import_contract_list_model->queryImport('DELETE FROM import_contract_list WHERE import_contract='.$_POST['data']);
                        $import_contract_model->deleteImport($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|import_contract|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $import_contract_model = $this->model->get('importcontractModel');
            $import_contract_list_model = $this->model->get('importcontractlistModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');

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

            /*$nameWorksheet = trim($objWorksheet->getTitle()); // tên sheet là tháng lương (8.2014 => 08/2014)
            $day = explode(".", $nameWorksheet); 
            $ngaythang = (strlen($day[0]) < 2 ? "0".$day[0] : $day[0] )."-".$day[1] ;
            $ngaythang = '01-'.$ngaythang;*/

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            $tongchiphi = 0;

            $y = 0;

            for ($row = 2; $row <= $highestRow; ++ $row) {
                $val = array();
                for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                    $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                    // Check if cell is merged
                    foreach ($objWorksheet->getMergeCells() as $cells) {
                        if ($cell->isInRange($cells)) {
                            $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                            $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                            if ($col == 1) {
                                $y++;
                            }
                            
                            break;
                            
                        }
                    }

                    $val[] = $cell->getCalculatedValue();
                    //here's my prob..
                    //echo $val;
                }

                /*$ngay = $val[1];
                $ngaythang = PHPExcel_Shared_Date::ExcelToPHP($ngay);                                      
                $ngaythang = $ngaythang+86400;

                $ngaythang = strtotime(date('d-m-Y',$ngaythang));*/


                if (trim($val[0]) != null && trim($val[1]) != null && trim($val[2]) != null && trim($val[3]) != null) {
                    $brand = trim($val[0]);
                    $size = trim($val[1]);
                    $pattern = trim($val[2]);
                    
                    if ($tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))) {
                        $brand_id = $tire_brand_model->getTireByWhere(array('tire_brand_name' => $brand))->tire_brand_id;
                    }
                    else{
                        $tire_brand_model->createTire(array('tire_brand_name' => $brand));
                        $tire_brand_id = $tire_brand_model->getLastTire()->tire_brand_id;
                        $brand_id = $tire_brand_id;
                    }

                    if ($tire_size_model->getTireByWhere(array('tire_size_number' => $size))) {
                        $size_id = $tire_size_model->getTireByWhere(array('tire_size_number' => $size))->tire_size_id;
                    }
                    else{
                        $tire_size_model->createTire(array('tire_size_number' => $size));
                        $tire_size_id = $tire_size_model->getLastTire()->tire_size_id;
                        $size_id = $tire_size_id;
                    }

                    if ($tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))) {
                        $pattern_id = $tire_pattern_model->getTireByWhere(array('tire_pattern_name' => $pattern))->tire_pattern_id;
                    }
                    else{
                        $tire_pattern_model->createTire(array('tire_pattern_name' => $pattern));
                        $tire_pattern_id = $tire_pattern_model->getLastTire()->tire_pattern_id;
                        $pattern_id = $tire_pattern_id;
                    }

                    

                    $data = array(
                        'tire_brand'=>$brand_id,
                        'tire_size'=>$size_id,
                        'tire_pattern'=>$pattern_id,
                        'tire_number'=>$val[3],
                    );
                    $import_contract_list_model->createImport($data);
                    
                }
                


            }
            //return $this->view->redirect('importcontract');
            echo "Thêm thành công!";
        }
        //$this->view->show('importcontract/import');

    }
    
    public function getgoing(){
            $import_contract_list_model = $this->model->get('importcontractlistModel');
            
            $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_brand AND tire_size.tire_size_id = tire_size AND tire_pattern.tire_pattern_id = tire_pattern');
            $lists = $import_contract_list_model->getAllImport(array('where'=>'import_contract IS NULL'),$join);

            $str = "";
            $str2 = "";
            $i = 1;
            $sl = 0;

            if ($lists) {
                foreach ($lists as $tire) {
                    
                    $sl += $tire->tire_number;
                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off" value="'.$tire->tire_brand_name.'" data="'.$tire->tire_brand.'">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off" value="'.$tire->tire_size_number.'" data="'.$tire->tire_size.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off" value="'.$tire->tire_pattern_name.'" data="'.$tire->tire_pattern.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off" value="'.$tire->tire_number.'" data="'.$tire->import_contract_list_id.'" ></td>';
                        
                    
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off"></td>';
                
                $str .= '</tr>';

            }

            $str2 .= '<tr>';
            $str2 .= '<th class="width-3"></th>
                    <th class="width-10">Tổng</th>
                    <th class="width-10"></th>
                    <th class="width-7"></th>
                    <th class="width-5 numbers" id="tongsl">'.$sl.'</th>
                    <th style="width:5px"></th>';
            
            $str2 .= '</tr>';
            

            $arr = array(
                'hang'=>$str,
                'tong'=>$str2,
            );
            echo json_encode($arr);
    }
    public function getitemadd(){
            $import_contract_list_model = $this->model->get('importcontractlistModel');
            $import_contract_model = $this->model->get('importcontractModel');
            
            $orders = $import_contract_model->getImport($_POST['order']);
            $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_brand AND tire_size.tire_size_id = tire_size AND tire_pattern.tire_pattern_id = tire_pattern');
            $lists = $import_contract_list_model->getAllImport(array('where'=>'import_contract = '.$_POST['order']),$join);

            $str = "";
            $str2 = "";
            $i = 1;
            $sl = 0;

            if ($lists) {
                foreach ($lists as $tire) {
                    
                    $sl += $tire->tire_number;

                    $str .= '<tr>';
                    $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off" value="'.$tire->tire_brand_name.'" data="'.$tire->tire_brand.'">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off" value="'.$tire->tire_size_number.'" data="'.$tire->tire_size.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off" value="'.$tire->tire_pattern_name.'" data="'.$tire->tire_pattern.'">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off" value="'.$tire->tire_number.'" data="'.$tire->import_contract_list_id.'" ></td>';
                    
                    $str .= '</tr>';

                  $i++;
                }
            }
            else{
                $str .= '<tr>';
                $str .= '<td class="width-3">'.$i.'</td>
                        <td class="width-10">
                          <input type="text" name="tire_brand[]" class="tire_brand keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_2"></ul>
                        </td>
                        <td class="width-10">
                          <input type="text" name="tire_size[]" class="tire_size keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-7">
                          <input type="text" name="tire_pattern[]" class="tire_pattern keep-val" required="required" autocomplete="off">
                          <ul class="name_list_id_3"></ul>
                        </td>
                        <td class="width-5"><input type="text" name="tire_number[]" class="tire_number numbers text-right" required="required" autocomplete="off"></td>';
                
                $str .= '</tr>';

            }

            $str2 .= '<tr>';
            $str2 .= '<th class="width-3"></th>
                    <th class="width-10">Tổng</th>
                    <th class="width-10"></th>
                    <th class="width-7"></th>
                    <th class="width-5 numbers" id="tongsl">'.$sl.'</th>
                    <th style="width:5px"></th>';
            
            $str2 .= '</tr>';
            

            $arr = array(
                'hang'=>$str,
                'tong'=>$str2,
            );
            echo json_encode($arr);
    }
    

}
?>