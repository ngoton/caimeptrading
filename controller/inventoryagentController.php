<?php
Class inventoryagentController Extends baseController {
    
    public function index() {
        $this->view->disableLayout();
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Tồn kho lốp xe';

        $tire_brand_group_model = $this->model->get('tirebrandgroupModel');
        $tire_going_model = $this->model->get('tiregoingModel');
        $import_tire_list_model = $this->model->get('importtirelistModel');

        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_desired_model = $this->model->get('tiredesiredModel');
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_sizes = $tire_size_model->getAllTire();
        $this->view->data['tire_sizes'] = $tire_sizes;

        $tonkho_brand_group = array();
        $tonkho = array();
        $ban = array();
        $dathang = array();
        $dangve = array();
        $dangorder = array();
        $kho_brand = array();
        $ban_brand = array();
        $dathang_brand = array();
        $nhaphang_brand = array();
        $dangve_brand = array();
        $dangorder_brand = array();

        $pattern_data = array();
        $size_data = array();

        $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
        foreach ($order_tires as $order) {
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id'));
            foreach ($order_tire_lists as $list) {
                $pt_type = explode(',', $list->tire_pattern_type);
                for ($l=0; $l < count($pt_type); $l++) { 
                    $ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number] = isset($ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number])?$ban[$list->tire_brand_group][$pt_type[$l]][$list->tire_size_number]+$list->tire_number:$list->tire_number;

                    $pattern_data[$pt_type[$l]] = true;
                    $size_data[$pt_type[$l]][$list->tire_size_number] = true;
                }
            }
        }
        $tire_goings = $tire_going_model->getAllTire(null,array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND (status IS NULL OR status=0)')); //tire_brand thay tire_brand_group
        $tire_orders = $import_tire_list_model->getAllImport(null,array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id AND import_tire_list_id NOT IN (SELECT import_tire_list FROM tire_going WHERE import_tire_list IS NOT NULL)')); //tire_brand thay tire_brand_group
        $tire_desireds = $tire_desired_model->getAllTire(null,array('table'=>'tire_size','where'=>'tire_size=tire_size_id AND (tire_desired_status IS NULL OR tire_desired_status=0)')); //tire_brand thay tire_brand_group
        $tire_buys = $tire_buy_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_buy_pattern=tire_pattern_id and tire_buy_size=tire_size_id AND tire_buy_brand=tire_brand_id'));
        $tire_sales = $tire_sale_model->getAllTire(null,array('table'=>'tire_pattern, tire_size, tire_brand','where'=>'tire_pattern=tire_pattern_id and tire_size=tire_size_id AND tire_brand=tire_brand_id'));
        
        $tire_brand_groups = $tire_brand_group_model->getAllTire();

        $last_code = 0;
        foreach ($tire_goings as $going) {
            $pt_type = explode(',', $going->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number] = isset($dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number])?$dangve[$going->tire_brand_group][$pt_type[$l]][$going->tire_size_number]+$going->tire_number:$going->tire_number;

                $pattern_data[$pt_type[$l]] = true;
                $size_data[$pt_type[$l]][$going->tire_size_number] = true;
            }
            
            $last_code = $last_code==0?$going->code:$last_code;
        }

        foreach ($tire_orders as $order) {
            $pt_type = explode(',', $order->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number] = isset($dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number])?$dangorder[$order->tire_brand_group][$pt_type[$l]][$order->tire_size_number]+$order->tire_number:$order->tire_number;

                $pattern_data[$pt_type[$l]] = true;
                $size_data[$pt_type[$l]][$order->tire_size_number] = true;
            }
            
        }

        foreach ($tire_desireds as $desired) {
            $dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number] = isset($dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number])?$dathang[$desired->tire_brand][$desired->tire_pattern][$desired->tire_size_number]+$desired->tire_number:$desired->tire_number;

            $pattern_data[$desired->tire_pattern] = true;
            $size_data[$desired->tire_pattern][$desired->tire_size_number] = true;
        }

        foreach ($tire_buys as $buy) {
            $pt_type = explode(',', $buy->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number] = isset($tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number])?$tonkho[$buy->tire_brand_group][$pt_type[$l]][$buy->tire_size_number]+$buy->tire_buy_volume:$buy->tire_buy_volume;
                
                $tonkho_brand_group[$buy->tire_brand_group] = isset($tonkho_brand_group[$buy->tire_brand_group])?$tonkho_brand_group[$buy->tire_brand_group]+$buy->tire_buy_volume:$buy->tire_buy_volume;
                $pattern_data[$pt_type[$l]] = isset($pattern_data[$pt_type[$l]])?$pattern_data[$pt_type[$l]]+$buy->tire_buy_volume:$buy->tire_buy_volume;
                $size_data[$pt_type[$l]][$buy->tire_size_number] = isset($size_data[$pt_type[$l]][$buy->tire_size_number])?$size_data[$pt_type[$l]][$buy->tire_size_number]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            }
        }

        foreach ($tire_sales as $sale) {
            $pt_type = explode(',', $sale->tire_pattern_type);
            for ($l=0; $l < count($pt_type); $l++) {
                $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number] = isset($tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number])?$tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]-$sale->volume:$sale->volume;
                $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number] = $tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]!=0?$tonkho[$sale->tire_brand_group][$pt_type[$l]][$sale->tire_size_number]:null;

                $tonkho_brand_group[$sale->tire_brand_group] = isset($tonkho_brand_group[$sale->tire_brand_group])?$tonkho_brand_group[$sale->tire_brand_group]-$sale->volume:(0-$sale->volume);
                $pattern_data[$pt_type[$l]] = isset($pattern_data[$pt_type[$l]])?$pattern_data[$pt_type[$l]]-$sale->volume:(0-$sale->volume);
                $size_data[$pt_type[$l]][$sale->tire_size_number] = isset($size_data[$pt_type[$l]][$sale->tire_size_number])?$size_data[$pt_type[$l]][$sale->tire_size_number]-$sale->volume:(0-$sale->volume);
            }
        }


        foreach ($tire_brand_groups as $brand) {
            if (isset($tonkho_brand_group[$brand->tire_brand_group_id]) && $tonkho_brand_group[$brand->tire_brand_group_id]!=0) {
                $kho_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $kho_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($ban[$brand->tire_brand_group_id])) {
                $ban_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $ban_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dathang[$brand->tire_brand_group_id])) {
                $dathang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dathang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dangve[$brand->tire_brand_group_id])) {
                $dangve_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dangve_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
            if (isset($dangorder[$brand->tire_brand_group_id])) {
                $dangorder_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
                $dangorder_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;

                $nhaphang_brand[$brand->tire_brand_group_id]['name'] = $brand->tire_brand_group_name;
                $nhaphang_brand[$brand->tire_brand_group_id]['id'] = $brand->tire_brand_group_id;
            }
        }

        $this->view->data['last_code'] = $last_code;

        $this->view->data['tonkhos'] = $tonkho;
        $this->view->data['orders'] = $ban;
        $this->view->data['dathangs'] = $dathang;
        $this->view->data['dangves'] = $dangve;
        $this->view->data['dangorders'] = $dangorder;
        $this->view->data['brand_tonkhos'] = $kho_brand;
        $this->view->data['brand_orders'] = $ban_brand;
        $this->view->data['brand_dathangs'] = $dathang_brand;
        $this->view->data['brand_nhaphangs'] = $nhaphang_brand;
        $this->view->data['brand_dangves'] = $dangve_brand;
        $this->view->data['brand_dangorders'] = $dangorder_brand;
        $this->view->data['pattern_data'] = $pattern_data;
        $this->view->data['size_data'] = $size_data;
        
        /*************/
        $this->view->show('inventoryagent/index');
    }
    public function order(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $join = array('table'=>'tire_pattern,tire_brand,tire_size,order_tire,user,customer','where'=>'order_tire.customer=customer_id AND order_tire.sale = user_id AND tire_pattern = tire_pattern_id AND tire_brand = tire_brand_id AND tire_size = tire_size_id AND order_tire = order_tire_id AND (order_tire_status IS NULL OR order_tire_status = 0)');

        $data = array(
            'where' => 'tire_brand='.$this->registry->router->param_id.' AND tire_size='.$this->registry->router->page.' AND tire_pattern='.$this->registry->router->order_by,
        );

        $orders = $tire_order_list_model->getAllTire($data,$join);
        $this->view->data['tire_orders'] = $orders;

        $this->view->show('inventoryagent/order');
    }
    
}
?>