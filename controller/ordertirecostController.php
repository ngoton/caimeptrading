<?php
Class ordertirecostController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Chi phí lô hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_number';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
        }

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));
        
        $join = array('table'=>'order_tire, shipment_vendor','where'=>'vendor=shipment_vendor_id AND order_tire=order_tire_id');
        $data = array(
            'where' => 'delivery_date >='.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
        );

        $ordercost_model = $this->model->get('ordertirecostModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;

        
        $tongsodong = count($ordercost_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['limit'] = $limit;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => 'delivery_date >='.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc),
            );


        if ($keyword != '') {
            $search = ' AND ( order_number LIKE "%'.$keyword.'%" 
                        OR shipment_vendor_name LIKE "%'.$keyword.'%"  )';
            
                $data['where'] .= $search;
        }

        $ordercost = $ordercost_model->getAllTire($data,$join);
        $this->view->data['ordercost'] = $ordercost;

        $payable_model = $this->model->get('payableModel');

        $pay_data = array();
        foreach ($ordercost as $order) {
            $payables = $payable_model->getCostsByWhere(array('order_tire'=>$order->order_tire,'vendor'=>$order->vendor,'cost_type'=>$order->order_tire_cost_type));
            $pay_data[$order->order_tire_cost_id] = $payables->pay_money;
        }
        $this->view->data['pay_data'] = $pay_data;

        $this->view->data['lastID'] = isset($ordercost_model->getLastTire()->order_tire_cost_id)?$ordercost_model->getLastTire()->order_tire_cost_id:0;

        $this->view->show('ordertirecost/index');
    }


}
?>