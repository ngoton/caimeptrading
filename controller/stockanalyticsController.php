<?php
Class stockanalyticsController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng theo dõi hàng hóa';

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
                $hangorder[$order->import_tire_order_id][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name] = isset($hangorder[$order->import_tire_order_id][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name])?$hangorder[$order->import_tire_order_id][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name]+$tire_order->tire_number:$tire_order->tire_number;
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
                $dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
            }
        }
        

        $this->view->data['orders'] = $orders;
        $this->view->data['hangorder'] = $hangorder;
        $this->view->data['tonkho'] = $tonkho;
        $this->view->data['dathang'] = $dathang;
        $this->view->data['dangve'] = $dangve;
        $this->view->data['tire_goings'] = $tire_goings;
        
        /*************/
        $this->view->show('stockanalytics/index');
    }
      
    
}
?>