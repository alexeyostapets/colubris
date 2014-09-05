<?php
class endpoint_v1_task extends Endpoint_v1_General {

    public $model_class = 'Task';

    function init() {
        parent::init();
    }

    function get_getStatuses(){
        $data = array();
        foreach ($this->model->task_statuses as $status){
            $data[] = array('id' => $status, 'name' => $status);
        }
        return[
            'result' => 'success',
            'data'   => $data
        ];
    }
}