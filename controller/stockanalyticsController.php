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
        $chodat = array();

        $minDate = 0;
        $maxDate = 0;
        
        $orders = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status IS NULL OR import_tire_order_status=1 OR import_tire_order_status=0)','order_by'=>'import_tire_order_date ASC'));
        foreach ($orders as $order) {
            if ($order->import_tire_order_month > 0) {
                if ($minDate==0 && $maxDate==0) {
                    $minDate = $order->import_tire_order_month;
                    $maxDate = $order->import_tire_order_month;
                }

                if ($order->import_tire_order_month < $minDate) {
                    $minDate = $order->import_tire_order_month;
                }
                if ($order->import_tire_order_month > $maxDate) {
                    $maxDate = $order->import_tire_order_month;
                }

            }
            

            $tire_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$order->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($tire_orders as $tire_order) {
                if ($order->import_tire_order_month > 0) {
                    $hangorder[date('m-Y',$order->import_tire_order_month)][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name] = isset($hangorder[date('m-Y',$order->import_tire_order_month)][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name])?$hangorder[date('m-Y',$order->import_tire_order_month)][$tire_order->tire_brand_name.$tire_order->tire_size_number.$tire_order->tire_pattern_name]+$tire_order->tire_number:$tire_order->tire_number;
                }
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


        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status=2 OR import_tire_order_status=4)','order_by'=>'import_tire_order_status ASC,import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
                if ($tire_going->import_tire_order_status==2) {
                    $chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
                }
            }
        }

        $batdau = '01-01-'.date('Y');
        $ketthuc = date('t-m-Y');   
        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $data = array(
            'where' => 'import_tire_order_expect_date >= '.strtotime($batdau).' AND import_tire_order_expect_date < '.strtotime($ngayketthuc),
        );

        $import_tire_orders = $import_tire_order_model->getAllImport($data,null);
        $total_cont = 0;
        foreach ($import_tire_orders as $import_tire_order) {
            if ($import_tire_order->import_tire_order_status == 3 && $import_tire_order->import_tire_order_total >= 200) {
                $total_cont += $import_tire_order->import_tire_order_cont_total;
            }
        }
        $this->view->data['total_cont'] = $total_cont-9;
        
        $this->view->data['minDate'] = $minDate;
        $this->view->data['maxDate'] = $maxDate;

        $this->view->data['orders'] = $orders;
        $this->view->data['hangorder'] = $hangorder;
        $this->view->data['tonkho'] = $tonkho;
        $this->view->data['dathang'] = $dathang;
        $this->view->data['dangve'] = $dangve;
        $this->view->data['chodat'] = $chodat;
        $this->view->data['tire_goings'] = $tire_goings;
        
        /*************/
        $this->view->show('stockanalytics/index');
    }
    public function going(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['data'])) {
            $import_tire_order_model = $this->model->get('importtireorderModel');
            $import_tire_list_model = $this->model->get('importtirelistModel');
            $tire_going_model = $this->model->get('tiregoingModel');

            $id = $_POST['data'];
            $status = $_POST['value'];
            $date = strtotime($_POST['date']);

            $import_tire_order_model->updateImport(array('import_tire_order_status'=>$status,'import_tire_order_expect_date'=>$date),array('import_tire_order_id'=>$id));
            $import_orders = $import_tire_order_model->getImport($id);
            
            $list_orders = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$id));
            foreach ($list_orders as $order) {
                if ($import_orders->import_tire_order_status==2) {
                    $tire_going = array(
                    'tire_going_date' => $import_orders->import_tire_order_expect_date,
                    'code' => $import_orders->import_tire_order_code,
                    'tire_size' => $order->tire_size,
                    'tire_pattern' => $order->tire_pattern,
                    'tire_brand' => $order->tire_brand,
                    'tire_number' => $order->tire_number,
                    'import_tire_list' => $order->import_tire_list_id,
                    'status' => 0,
                    );
                    if (!$tire_going_model->getTireByWhere(array('import_tire_list' => $order->import_tire_list_id))) {
                        $tire_going_model->createTire($tire_going);
                    }
                    else{
                        $tire_going_model->updateTire($tire_going,array('import_tire_list' => $order->import_tire_list_id));
                    }
                    
                }
                else{
                    $tire_going_model->queryTire('DELETE FROM tire_going WHERE import_tire_list='.$order->import_tire_list_id);
                }
            }


            echo "Cập nhật thành công";
        }
    }
    function export(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

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
        $chodat = array();
        
        $orders = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status IS NULL OR import_tire_order_status=1 OR import_tire_order_status=0)','order_by'=>'import_tire_order_month ASC'));
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


        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status=2 OR import_tire_order_status=4)','order_by'=>'import_tire_order_status ASC,import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id),array('table'=>'tire_size, tire_pattern, tire_brand','where'=>'tire_brand=tire_brand_id AND tire_pattern=tire_pattern_id AND tire_size=tire_size_id')); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$dangve[$tire_going->import_tire_order_id][$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
                if ($tire_going->import_tire_order_status==2) {
                    $chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name] = isset($chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name])?$chodat[$going->tire_brand_name.$going->tire_size_number.$going->tire_pattern_name]+$going->tire_number:$going->tire_number;
                }
            }
        }

        $data = array(
            "Goodride7.50R16CR926",
            "Goodride9.00-20CL946",
            "Goodride10.00R20CR926",
            "Goodride11.00R20CR926",
            "Goodride11.00R20CR976A",
            "Goodride11.00R20CM987",
            "Goodride12.00R20CR926",
            "Goodride11R22.5CR976A",
            "Goodride11R22.5AS668",
            "Goodride11R22.5CM983",
            "Goodride295/75R22.5CR960A",
            "Goodride295/75R22.5CM983",
            "Goodride12R22.5CR926",
            "Goodride12R22.5CR976A",
            "Goodride12R22.5AS668",
            "Goodride12R22.5MD738",
            "Goodride12R22.5CM985",
            "Goodride12R22.5CB919",
            "Goodride11.00R20CB972",
            "Goodride12.00R20CM958",
            "Goodride12.00R20CM913A",
            "Goodride12.00R20EZ356",
            "Goodride12.00R20CB919"
        );

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'BẢNG THEO DÕI HÀNG HÓA')
                ->setCellValue('A3', 'GOODRIDE')
                ->setCellValue('A4', '7.50R16')
                ->setCellValue('A5', '9.00-20')
                ->setCellValue('A6', '10.00R20')
                ->setCellValue('A7', '11.00R20')
               ->setCellValue('A8', '11.00R20')
               ->setCellValue('A9', '11.00R20')
               
               ->setCellValue('A10', '12.00R20')
               ->setCellValue('A11', '11R22.5')
                ->setCellValue('A12', '11R22.5')
                ->setCellValue('A13', '11R22.5')
                ->setCellValue('A14', '295/75R22.5')
               ->setCellValue('A15', '295/75R22.5')
                ->setCellValue('A16', '12R22.5')
                ->setCellValue('A17', '12R22.5')
                ->setCellValue('A18', '12R22.5')
               ->setCellValue('A19', '12R22.5')
               ->setCellValue('A20', '12R22.5')
               ->setCellValue('A21', '12R22.5')
               ->setCellValue('A22', '11.00R20')
               ->setCellValue('A23', '12.00R20')
               ->setCellValue('A24', '12.00R20')
               ->setCellValue('A25', '12.00R20')
               ->setCellValue('A26', '12.00R20')
               ->setCellValue('A27', 'TỔNG CỘNG')

                ->setCellValue('B4', 'CR926')
                ->setCellValue('B5', 'CL946')
                ->setCellValue('B6', 'CR926')
                ->setCellValue('B7', 'CR926')
               ->setCellValue('B8', 'CR976A')
               ->setCellValue('B9', 'CM987')
               
               ->setCellValue('B10', 'CR926')
               
               ->setCellValue('B11', 'CR976A')
                ->setCellValue('B12', 'AS668')
                ->setCellValue('B13', 'CM983')
                ->setCellValue('B14', 'CR960A')
               ->setCellValue('B15', 'CM983')
                ->setCellValue('B16', 'CR926')
                ->setCellValue('B17', 'CR976A')
                ->setCellValue('B18', 'AS668')
               ->setCellValue('B19', 'MD738')
               ->setCellValue('B20', 'CM985')
               ->setCellValue('B21', 'CB919')
               
               ->setCellValue('B22', 'CB972')
               ->setCellValue('B23', 'CM958')
               ->setCellValue('B24', 'CM913A')
               ->setCellValue('B25', 'EZ356')
               ->setCellValue('B26', 'CB919');
            
            
            $sum_arr = array();
            $cot = 'C';
            foreach ($orders as $order) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', $order->import_tire_order_code);
                $hang = 4;
                foreach ($data as $key) {
                    $t = isset($hangorder[$order->import_tire_order_id][$key])?$hangorder[$order->import_tire_order_id][$key]:null;
                    $sum_arr[$key] = isset($sum_arr[$key])?$sum_arr[$key]+$t:$t;

                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, $t);
                    $hang++;
                }
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

                $cot++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', 'Tổng order');
            $hang = 4;
            foreach ($data as $key) {
                 $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cot . $hang, isset($sum_arr[$key])?$sum_arr[$key]:null);
                $hang++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

            $objPHPExcel->getActiveSheet()->getStyle($cot.'3:'.$cot.$hang)->getFont()->setBold(true);
            $cot++;

            
            $sum_arr = array();
            
            foreach ($tire_goings as $tire_going) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', $tire_going->import_tire_order_code);
                $hang = 4;
                foreach ($data as $key) {
                    $t = isset($dangve[$tire_going->import_tire_order_id][$key])?$dangve[$tire_going->import_tire_order_id][$key]:null;
                    $sum_arr[$key] = isset($sum_arr[$key])?$sum_arr[$key]+$t:$t;

                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, $t);
                    $hang++;
                }
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');
                $cot++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', 'Đang về');
            $hang = 4;
            foreach ($data as $key) {
                 $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cot . $hang, isset($sum_arr[$key])?$sum_arr[$key]:null);
                $hang++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

            $objPHPExcel->getActiveSheet()->getStyle($cot.'3:'.$cot.$hang)->getFont()->setBold(true);
            $cot++;

            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', 'Đang chờ');
            $hang = 4;
            foreach ($data as $key) {
                 $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cot . $hang, isset($chodat[$key])?$chodat[$key]:null);
                $hang++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

            $objPHPExcel->getActiveSheet()->getStyle($cot.'3:'.$cot.$hang)->getFont()->setBold(true);
            $cot++;

            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', 'Tồn kho');
            $hang = 4;
            foreach ($data as $key) {
                 $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cot . $hang, isset($tonkho[$key])?$tonkho[$key]:null);
                $hang++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

            $objPHPExcel->getActiveSheet()->getStyle($cot.'3:'.$cot.$hang)->getFont()->setBold(true);
            $cot++;

            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . '3', 'Đặt hàng');
            $hang = 4;
            foreach ($data as $key) {
                 $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cot . $hang, isset($dathang[$key])?$dathang[$key]:null);
                $hang++;
            }
            $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue($cot . $hang, '=SUM('.$cot.'4:'.$cot.($hang-1).')');

            $objPHPExcel->getActiveSheet()->getStyle($cot.'3:'.$cot.$hang)->getFont()->setBold(true);
            //$cot++;
            

            $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$cot.'1');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:B3');
            $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':B'.$hang);
            $objPHPExcel->getActiveSheet()->getStyle('A1:B'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:B'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A3:'.$cot.'3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A3:'.$cot.'3')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

             

            $objPHPExcel->getActiveSheet()->getStyle('A3:'.$cot.$hang)->applyFromArray(
                array(
                    
                    'borders' => array(
                        'allborders' => array(
                          'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:'.$cot.'3')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':'.$cot.$hang)->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

             
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(10);
            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Stock analytics")
                            ->setSubject("Stock analytics")
                            ->setDescription("Stock analytics")
                            ->setKeywords("Stock analytics")
                            ->setCategory("Stock analytics");
            $objPHPExcel->getActiveSheet()->setTitle("Bang theo doi");

            $objPHPExcel->getActiveSheet()->freezePane('C4');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG THEO DÕI.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }
      
    
}
?>