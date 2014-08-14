<?php
/**
 * Created by Vadym Radvansky
 * Date: 7/8/14 12:12 PM
 */
class endpoint_v1_requirement extends Endpoint_v1_General {

    public $model_class = 'Requirement';

    function init() {
        parent::init();
    }

    function get_getForQuote() {
        $quote_id = $this->getQuoteId();
        $data = $this->model->addCondition('quote_id',$quote_id)->getRows();
        echo json_encode([
            'result' => 'success',
            'data'   => $data,
        ]);
        exit();

    }
    function get_saveAll() {
        $quote_id = $this->getQuoteId();
        echo $quote_id;
        exit();
    }
    private function getQuoteId() {
        $quote_id = $this->checkGetParameter('quote_id'); // method from trait
        return $quote_id;
    }
}