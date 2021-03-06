<?php

Class adminController Extends baseController {

    public function index(){

        $this->view->setLayout('admin');

        if (!isset($_SESSION['userid_logined'])) {

            return $this->view->redirect('user/login');

        }

        $this->view->data['lib'] = $this->lib;

        $this->view->data['title'] = 'Dashboard';



        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;

            $order = isset($_POST['order']) ? $_POST['order'] : null;

            $page = isset($_POST['page']) ? $_POST['page'] : null;

            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;

            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;

            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;

            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;

            $ngaytao = isset($_POST['ngaytao']) ? $_POST['ngaytao'] : null;

            $ngaytaobatdau = isset($_POST['ngaytaobatdau']) ? $_POST['ngaytaobatdau'] : null;

        }

        else{

            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_sale_date';

            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';

            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;

            $keyword = "";

            $limit = "";

            $batdau = '01-'.date('m-Y');

            $ketthuc = date('t-m-Y');

            $ngaytao = date('m-Y');

            $ngaytaobatdau = date('m-Y');

        }



        $this->view->data['limit'] = $limit;

        $this->view->data['page'] = $page;

        $this->view->data['order_by'] = $order_by;

        $this->view->data['order'] = $order;

        $this->view->data['batdau'] = $batdau;

        $this->view->data['ketthuc'] = $ketthuc;

        $this->view->data['ngaytao'] = $ngaytao;

        $this->view->data['ngaytaobatdau'] = $ngaytaobatdau;



        $sale_model = $this->model->get('salereportModel');

        $order_tire_model = $this->model->get('ordertireModel');

        $tire_sale_model = $this->model->get('tiresaleModel');

        $staff_model = $this->model->get('staffModel');

        $join = array('table'=>'user','where'=>'account=user_id');

        $staffs = $staff_model->getAllStaff(array('order_by'=>'staff_name ASC'),$join);

        $this->view->data['staffs'] = $staffs;



        $data = array(

            'where' => 'staff_id IN (SELECT sale FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).') AND ( (start_date <= '.strtotime($batdau).' AND end_date >= '.strtotime($ketthuc).') OR (start_date <= '.strtotime($batdau).' AND (end_date IS NULL OR end_date = 0) ) )',

        );
       


        

        $staff_sales = $staff_model->getAllStaff($data,$join);

        $this->view->data['staff_sales'] = $staff_sales;



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



        $data = array(

            'where' => 'sale_date < '.strtotime($batdau),

        );



        /*$sale_olds = $sale_model->getAllSale($data);



        $old2 = array();

        foreach ($sale_olds as $sale) {

            if (!in_array($sale->customer,$old2)) {

                $old2[] = $sale->customer;

            }

        }*/



        $c = array();



        $info_staff = array();



        if ($limit != "") {

            $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE sale = '.$limit.' AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'));

            //$sales = $sale_model->getAllSale(array('where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc)),array('table'=>'staff','where'=>'sale=account AND staff_id = '.$limit));

        }

        else{

            $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'));

            //$sales = $sale_model->getAllSale(array('where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc)));

        }


        $doanhthu = 0;

        $sanluong = 0;

        $khachhang = 0;

        $donhang = 0;


        foreach ($orders as $tire) {
            $doanhthu += $tire->total;

            $sanluong += $tire->order_tire_number;

            $donhang++;

            

            $info_staff['sl'][$tire->sale] = isset($info_staff['sl'][$tire->sale])?$info_staff['sl'][$tire->sale]+$tire->order_tire_number:$tire->order_tire_number;

            $info_staff['dh'][$tire->sale] = isset($info_staff['dh'][$tire->sale])?$info_staff['dh'][$tire->sale]+1:1;

            if (isset($c[$tire->sale])) {
                if(!in_array($tire->customer,$c[$tire->sale])){

                    $info_staff['kh'][$tire->sale] = isset($info_staff['kh'][$tire->sale])?$info_staff['kh'][$tire->sale]+1:1;

                }
            }
            else{
                $info_staff['kh'][$tire->sale] = 1;
            }
            

            $c[$tire->sale][] = $tire->customer;

            if (!in_array($tire->customer,$old)) {

                $info_staff['khmoi'][$tire->sale] = isset($info_staff['khmoi'][$tire->sale])?$info_staff['khmoi'][$tire->sale]+1:1;

                $khachhang++;

                $old[] = $tire->customer;

            }

        }



        $this->view->data['info_staff'] = $info_staff;





        if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 9) {

            if ($limit != "") {

                $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE sale = '.$limit.' AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'));

                //$sales = $sale_model->getAllSale(array('where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc)),array('table'=>'staff','where'=>'sale=account AND staff_id = '.$limit));

            }

            else{

                $orders = $order_tire_model->getAllTire(array('where'=>'order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'));

                //$sales = $sale_model->getAllSale(array('where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc)));

            }

        }

        else{

            $orders = $order_tire_model->getAllTire(array('where'=>'sale = '.$_SESSION['userid_logined'].' AND order_tire_id IN (SELECT order_tire FROM tire_sale WHERE tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date <= '.strtotime($ketthuc).')'));

            //$sales = $sale_model->getAllSale(array('where' => 'sale_type = 1 AND sale_date >= '.strtotime($batdau).' AND sale_date <= '.strtotime($ketthuc)),array('table'=>'staff','where'=>'sale=account AND staff_id = '.$_SESSION['userid_logined']));

        }



        

        $sl = array();

        $sa = array();

        $dt = array();

        $da = array();

        



        /*foreach ($sales as $sale) {

            $doanhthu += $sale->revenue_vat+$sale->revenue;

            $donhang++;

            if (!in_array($sale->customer,$old2)) {

                $khachhang++;

            }

            $sl['ngay'][(int)date('d',$sale->sale_date)] = isset($sl['ngay'][(int)date('d',$sale->sale_date)])?$sl['ngay'][(int)date('d',$sale->sale_date)]+$sale->revenue_vat+$sale->revenue:$sale->revenue_vat+$sale->revenue;

            $sl['thang'][(int)date('m',$sale->sale_date)] = isset($sl['thang'][(int)date('m',$sale->sale_date)])?$sl['thang'][(int)date('m',$sale->sale_date)]+$sale->revenue_vat+$sale->revenue:$sale->revenue_vat+$sale->revenue;

          

        }*/



        /*if ($_SESSION['role_logined'] == 1 || $_SESSION['role_logined'] == 9) {

            if ($limit != "") {

                $orders = $tire_sale_model->queryTire('SELECT sale,tire_sale_date,volume FROM tire_sale WHERE sale='.$limit.' AND tire_sale_date >= '.strtotime('01-01-2016').' AND tire_sale_date <= '.strtotime(date('t-m-Y')));

                

            }

            else{

                $orders = $tire_sale_model->queryTire('SELECT sale,tire_sale_date,volume FROM tire_sale WHERE tire_sale_date >= '.strtotime('01-01-2016').' AND tire_sale_date <= '.strtotime(date('t-m-Y')));

            }

        }

        else{

            $staff_account = $staff_model->getStaffByWhere(array('account'=>$_SESSION['userid_logined']));

            $orders = $tire_sale_model->queryTire('SELECT sale,tire_sale_date,volume FROM tire_sale WHERE sale='.$staff_account->staff_id.' AND tire_sale_date >= '.strtotime('01-01-2016').' AND tire_sale_date <= '.strtotime(date('t-m-Y')));

        }*/



        if ($limit != "") {

            $orders = $tire_sale_model->queryTire('SELECT order_tire,sale,tire_sale_date,volume,sell_price,sell_price_vat FROM tire_sale WHERE sale='.$limit.' AND tire_sale_date >= '.strtotime('01-01-2015').' AND tire_sale_date <= '.strtotime(date('t-m-Y')));

            

        }

        else{

            $orders = $tire_sale_model->queryTire('SELECT order_tire,sale,tire_sale_date,volume,sell_price,sell_price_vat FROM tire_sale WHERE tire_sale_date >= '.strtotime('01-01-2015').' AND tire_sale_date <= '.strtotime(date('t-m-Y')));

        }



        foreach ($orders as $tire) {
            $vat=0;
            $o = $order_tire_model->getTire($tire->order_tire);
            if ($o) {
                if ($o->check_price_vat!=1 && $o->order_tire_number>0) {
                    $vat = ($o->vat/$o->order_tire_number)*$tire->volume;
                }
                //$vat -= (($o->discount+$o->reduce)/$o->order_tire_number)*$tire->volume;
            }

            $sl[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)] = isset($sl[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)])?$sl[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)]+$tire->volume:$tire->volume;

            $sa[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)] = isset($sa[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)])?$sa[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)]+$tire->volume:$tire->volume;

          

            $dt[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)] = isset($dt[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)])?$dt[(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)]+$tire->volume*($tire->sell_price_vat>0?$tire->sell_price_vat:$tire->sell_price)+$vat:$tire->volume*($tire->sell_price_vat>0?$tire->sell_price_vat:$tire->sell_price)+$vat;

            $da[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)] = isset($da[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)])?$da[$tire->sale][(int)date('m',$tire->tire_sale_date)][date('Y',$tire->tire_sale_date)]+$tire->volume*($tire->sell_price_vat>0?$tire->sell_price_vat:$tire->sell_price)+$vat:$tire->volume*($tire->sell_price_vat>0?$tire->sell_price_vat:$tire->sell_price)+$vat;

            

        }



        // $start = date('d',strtotime($batdau));

        // $start_month = date('m',strtotime($batdau));

        // $start_year = date('Y',strtotime($batdau));

        // $end = date('d',strtotime($ketthuc));

        // $end_month = date('m',strtotime($ketthuc));

        // $end_year = date('Y',strtotime($ketthuc));


        // $join = array('table'=>'user','where'=>'account=user_id');
        // $staff_datas = $staff_model->getAllStaff(array('where'=>'(staff_id=67 OR staff_id=66 OR staff_id=65 OR staff_id=64 OR staff_id=63 OR staff_id=14 OR staff_id=12 OR staff_id=2)'),$join);

        // $this->view->data['staff_datas'] = $staff_datas;

        $staff_datas = $staff_sales;
        $this->view->data['staff_datas'] = $staff_datas;


        $start = $month = strtotime('01-01-2015');

        $end = strtotime(date('t-m-Y'));

        $u = 0;


        while($month < $end)

        {
            $ss=0;
            $dd=0;

            $graph[$u]['y'] = date('Y-m',$month);

            $graph[$u]['item1'] = isset($sl[(int)date('m',$month)][date('Y',$month)])?$sl[(int)date('m',$month)][date('Y',$month)]:0;

            $graph2[$u]['y'] = date('Y-m',$month);

            $graph2[$u]['item2'] = isset($dt[(int)date('m',$month)][date('Y',$month)])?$dt[(int)date('m',$month)][date('Y',$month)]:0;



            foreach ($staff_datas as $st) {
                if ($st->user_group!=10) {
                    $graph[$u]['staff'.$st->staff_id] = isset($sa[$st->staff_id][(int)date('m',$month)][date('Y',$month)])?$sa[$st->staff_id][(int)date('m',$month)][date('Y',$month)]:0;
                    $graph2[$u]['staff'.$st->staff_id] = isset($da[$st->staff_id][(int)date('m',$month)][date('Y',$month)])?$da[$st->staff_id][(int)date('m',$month)][date('Y',$month)]:0;
                }
                else{
                    $ss += isset($sa[$st->staff_id][(int)date('m',$month)][date('Y',$month)])?$sa[$st->staff_id][(int)date('m',$month)][date('Y',$month)]:0;
                    $dd += isset($da[$st->staff_id][(int)date('m',$month)][date('Y',$month)])?$da[$st->staff_id][(int)date('m',$month)][date('Y',$month)]:0;
                }
                

            }
            $graph[$u]['seo'] = $ss;
            $graph2[$u]['seo'] = $dd;
            



            $month = strtotime("+1 month", $month);

            $u++;

        }



        

        $graph = str_replace('"},{"i','","i',json_encode($graph));

        $graph2 = str_replace('"},{"i','","i',json_encode($graph2));



        $this->view->data['doanhthu'] = $doanhthu;

        $this->view->data['sanluong'] = $sanluong;

        $this->view->data['khachhang'] = $khachhang;

        $this->view->data['donhang'] = $donhang;

        $this->view->data['graph'] = $graph;

        $this->view->data['graph2'] = $graph2;

        $this->view->data['sl'] = $sl;



        $tire_buy_model = $this->model->get('tirebuyModel');



        $query = "SELECT *,SUM(tire_buy_volume) AS soluong FROM tire_buy, tire_brand, tire_size, tire_pattern WHERE tire_brand.tire_brand_id = tire_buy.tire_buy_brand AND tire_size.tire_size_id = tire_buy.tire_buy_size AND tire_pattern.tire_pattern_id = tire_buy.tire_buy_pattern GROUP BY tire_buy_brand,tire_buy_size,tire_buy_pattern ORDER BY tire_brand_name ASC, tire_size_number ASC, tire_pattern_name ASC";

        $tire_buys = $tire_buy_model->queryTire($query);

        $this->view->data['tire_buys'] = $tire_buys;



        $link_picture = array();



        $sell = array();

        $stock = array();

        foreach ($tire_buys as $tire_buy) {

            //$stock[$tire_buy->tire_brand_group] = isset($stock[$tire_buy->tire_brand_group])?$stock[$tire_buy->tire_brand_group]+$tire_buy->soluong:$tire_buy->soluong;

            $stock[$tire_buy->tire_brand_region] = isset($stock[$tire_buy->tire_brand_region])?$stock[$tire_buy->tire_brand_region]+$tire_buy->soluong:$tire_buy->soluong;

            $link_picture[$tire_buy->tire_buy_id]['image'] = $tire_buy->tire_pattern_name.'.jpg';



            $data_sale = array(

                'where'=>'tire_brand='.$tire_buy->tire_buy_brand.' AND tire_size='.$tire_buy->tire_buy_size.' AND tire_pattern='.$tire_buy->tire_buy_pattern,

            );

            $tire_sales = $tire_sale_model->getAllTire($data_sale);



            foreach ($tire_sales as $tire_sale) {

                

                if ($tire_sale->customer != 119) {

                    $sell[$tire_buy->tire_buy_id]['number'] = isset($sell[$tire_buy->tire_buy_id]['number'])?$sell[$tire_buy->tire_buy_id]['number']+$tire_sale->volume:$tire_sale->volume;

                    //$stock[$tire_buy->tire_brand_group] = isset($stock[$tire_buy->tire_brand_group])?$stock[$tire_buy->tire_brand_group]-$tire_sale->volume:(0-$tire_sale->volume);

                    $stock[$tire_buy->tire_brand_region] = isset($stock[$tire_buy->tire_brand_region])?$stock[$tire_buy->tire_brand_region]-$tire_sale->volume:(0-$tire_sale->volume);

                }

                

            }

        }



        $this->view->data['link_picture'] = $link_picture;



        $this->view->data['tire_buys'] = $tire_buys;

        $this->view->data['sell'] = $sell;

        $this->view->data['stock'] = $stock;



        $this->view->show('admin/index');



    }

    public function queryscript(){

        $costs_model = $this->model->get('costsModel');

        $costs_model->queryCosts('ALTER TABLE `costs` ADD `additional` INT NULL');

    }

    public function autoscript(){

    	$costs_model = $this->model->get('costsModel');

    	$payable_model = $this->model->get('payableModel');



    	$today = strtotime(date('d-m-Y H:i:s'));



        $data = array(

            'approve' => 10,

        );



        $data_costs1 = array(

            'where' => '(pending IS NULL OR pending=0) AND (approve IS NULL OR approve <= 0) AND (check_equipment > 0 OR check_energy > 0 )',

        );

        $costs1 = $costs_model->getAllCosts($data_costs1);

        foreach ($costs1 as $cost) {

            $costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

        }

        



    	$data_costs = array(

    		'where' => '(pending IS NULL OR pending=0) AND (approve IS NULL OR approve <= 0) AND ( money <= 5000000 OR money_in > 0 )',

    	);



    	$data_payable = array(

    		'where' => '(pending IS NULL OR pending=0) AND approve2 > 0 AND (approve IS NULL OR approve <= 0)',

    	);



    	



    	$costs = $costs_model->getAllCosts($data_costs);

    	$payables = $payable_model->getAllCosts($data_payable);



    	foreach ($costs as $cost) {

    		$hourdiff = round(($today - $cost->costs_create_date)/3600, 1);



    		if ( ($cost->money <= 500000 || $cost->money_in > 0 ) && $hourdiff >= 12) {

    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

    		}

    		elseif ($cost->money > 500000 && $cost->money <= 1000000 && $hourdiff >= 24) {

    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

    		}

    		elseif ($cost->money > 1000000 && $cost->money <= 1500000 && $hourdiff >= 36) {

    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

    		}

    		elseif ($cost->money > 1500000 && $cost->money <= 2000000 && $hourdiff >= 60) {

    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

    		}

    		elseif ($cost->money > 2000000 && $cost->money <= 5000000 && $hourdiff >= 120) {

    			$costs_model->updateCosts($data,array('costs_id'=>$cost->costs_id));

    		}

    	}



    	foreach ($payables as $payable) {

    		$hourdiff = round(($today - $payable->payable_create_date)/3600, 1);



    		if ($payable->money <= 500000 && $hourdiff >= 12) {

    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));

    		}

    		elseif ($payable->money > 500000 && $payable->money <= 1000000 && $hourdiff >= 24) {

    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));

    		}

    		elseif ($payable->money > 1000000 && $payable->money <= 1500000 && $hourdiff >= 36) {

    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));

    		}

    		elseif ($payable->money > 1500000 && $payable->money <= 2000000 && $hourdiff >= 60) {

    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));

    		}

    		elseif ($payable->money > 2000000 && $payable->money <= 5000000 && $hourdiff >= 120) {

    			$payable_model->updateCosts($data,array('payable_id'=>$payable->payable_id));

    		}

    	}



        date_default_timezone_set("Asia/Ho_Chi_Minh"); 

        $filename = "cron_logs.txt";

        $text = date('d/m/Y H:i:s')."|cron|"."edit"."\n"."\r\n";

        

        $fh = fopen($filename, "a") or die("Could not open log file.");

        fwrite($fh, $text) or die("Could not write file!");

        fclose($fh);



    }



    public function checklockuser(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if ($_POST['data'] == 0) {

                echo 0;

            }

            else{

                $user_model = $this->model->get('userModel');

            

                $user = $user_model->getUserByWhere(array('user_id' => $_POST['data']));

                echo $user->user_lock;

            }

            

        }

    }
    public function checkorderexpired(){
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $today = date('d-m-Y');
        $before = date('d-m-Y', strtotime($today. ' - 15 days'));

        $orders = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status=0) AND (approve IS NULL OR approve=0) AND id_order_agent > 0 AND order_tire_date <= '.strtotime($before)));
        $arr = array();
        foreach ($orders as $order) {
            $ton = 0;
            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($order_lists as $order_list) {
                $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$order_list->tire_brand.' AND tire_buy_size = '.$order_list->tire_size.' AND tire_buy_pattern = '.$order_list->tire_pattern));
                foreach ($tire_buys as $tire) {
                    $ton += $tire->tire_buy_volume;
                }

                $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$order_list->tire_brand.' AND tire_size = '.$order_list->tire_size.' AND tire_pattern = '.$order_list->tire_pattern));
                foreach ($tire_sales as $tire) {
                    $ton -= $tire->volume;
                }

            }
            if ($ton>0) {
                $arr[] = $order->id_order_agent;
            }
            
        }
        echo json_encode($arr);
    }
    public function checkorder48h(){
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_going_model = $this->model->get('tiregoingModel');
        $today = date('d-m-Y');
        $before = date('d-m-Y', strtotime($today. ' - 2 days'));

        $orders = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status=0) AND (order_number IS NULL OR order_number = "") AND order_tire_date <= '.strtotime($before)));
        $arr = array();
        foreach ($orders as $order) {
            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order->order_tire_id));
            foreach ($order_lists as $order_list) {
                $ton = 0;
                $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$order_list->tire_brand.' AND tire_buy_size = '.$order_list->tire_size.' AND tire_buy_pattern = '.$order_list->tire_pattern));
                foreach ($tire_buys as $tire) {
                    $ton += $tire->tire_buy_volume;
                }

                $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$order_list->tire_brand.' AND tire_size = '.$order_list->tire_size.' AND tire_pattern = '.$order_list->tire_pattern));
                foreach ($tire_sales as $tire) {
                    $ton -= $tire->volume;
                }

                $tire_goings = $tire_going_model->getAllTire(array('where'=>'(status IS NULL OR status != 1 ) AND tire_brand = '.$order_list->tire_brand.' AND tire_size = '.$order_list->tire_size.' AND tire_pattern = '.$order_list->tire_pattern));
                foreach ($tire_goings as $going) {
                    $ton += $going->tire_number;
                }

                $join = array('table'=>'order_tire','where'=>'order_tire != '.$order_list->order_tire.' AND order_tire=order_tire_id AND (order_tire_status IS NULL OR order_tire_status=0)');

                $order_details = $order_tire_list_model->getAllTire(array('where'=>'tire_brand = '.$order_list->tire_brand.' AND tire_size = '.$order_list->tire_size.' AND tire_pattern = '.$order_list->tire_pattern),$join);
                foreach ($order_details as $detail) {
                    $ton -= $detail->tire_number;
                }


                if ($ton<50) {
                    $arr[] = $order_list->order_tire_list_id;
                }
            }
            
            
        }
        
        $tire_pattern_model = $this->model->get('tirepatternModel');
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $customer_model = $this->model->get('customerModel');
        $order_tire_lock_model = $this->model->get('ordertirelockModel');

        foreach ($arr as $value) {
            $order_tire_list = $order_tire_list_model->getTire($value);

            $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

            $data_lock = array(
                'sale'=>$order_tire->sale,
                'tire_brand'=>$order_tire_list->tire_brand,
                'tire_size'=>$order_tire_list->tire_size,
                'tire_pattern'=>$order_tire_list->tire_pattern,
                'expired_date'=>strtotime(date('d-m-Y') . " +1 days"),
            );
            $order_tire_lock_model->createTire($data_lock);
            $order_tire_lock_model->queryTire('DELETE FROM order_tire_lock WHERE sale = '.$order_tire->sale.' AND expired_date < '.strtotime(date('d-m-Y') . " -1 days"));

            $order_tire_list_model->deleteTire($value);

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
            $total = $total - $discount;


            $data_order = array(
                'discount'=>$discount,
                'total'=>$total,
                'order_tire_number'=>$total_number,
                'vat'=> $vat,
            );


            $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

            
            if ($order_tire->id_order_agent>0) {
                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/deleteagentorder';

                // what post fields?
                $fields = array(
                    'tire_brand'=>$tire_brand_model->getTire($order_tire_list->tire_brand)->tire_brand_name,
                    'tire_pattern'=>$tire_pattern_model->getTire($order_tire_list->tire_pattern)->tire_pattern_name,
                    'tire_size'=>$tire_size_model->getTire($order_tire_list->tire_size)->tire_size_number,
                   'id_order_tire' => $order_tire->id_order_agent,
                   'id_order_vendor'=>$order_tire->order_tire_id,
                   'link_agent' => BASE_URL,
                   'total_number'=>$total_number,
                    'total'=>$total,
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


            echo "Xóa thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|admin|"."delete"."|".$value."|order_tire_list|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }



    public function notification(){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $costs_model = $this->model->get('costsModel');

            $payable_model = $this->model->get('payableModel');

            $total = "";

            

            if (isset($_SESSION['role_logined'])) {

                if($_SESSION['role_logined'] == 1){

                    $data_costs = array(

                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $data_payable = array(

                        'where' => 'approve3 > 0 AND (approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $costs = $costs_model->getAllCosts($data_costs);

                    $payables = $payable_model->getAllCosts($data_payable);



                    $total = 0;

                    foreach ($costs as $cost) {

                        $total++;

                    }

                    foreach ($payables as $payable) {

                        $total++;

                    }

                }

                else if($_SESSION['role_logined'] == 3){

                    $data_costs = array();



                    $data_payable = array(

                        'where' => '(approve3 IS NULL OR approve3 <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $payables = $payable_model->getAllCosts($data_payable);



                    $total = 0;

                    foreach ($payables as $payable) {

                        $total++;

                    }

                }

                else if($_SESSION['role_logined'] == 8){

                    $data_costs = array(

                        'where' => '(approve2 IS NULL OR approve2 <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $data_payable = array(

                        'where' => '(approve2 IS NULL OR approve2 <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $costs = $costs_model->getAllCosts($data_costs);

                    $payables = $payable_model->getAllCosts($data_payable);



                    $total = 0;

                    foreach ($costs as $cost) {

                        $total++;

                    }

                    foreach ($payables as $payable) {

                        $total++;

                    }

                }

                else{

                    $data_costs = array(

                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $data_payable = array(

                        'where' => '(approve IS NULL OR approve <= 0) AND (pay_money IS NULL OR pay_money != money)',

                    );



                    $costs = $costs_model->getAllCosts($data_costs);

                    $payables = $payable_model->getAllCosts($data_payable);



                    $total = 0;

                    foreach ($costs as $cost) {

                        $total++;

                    }

                    foreach ($payables as $payable) {

                        $total++;

                    }

                }

            }

            



            



            echo $total;



        }

    }





}

?>