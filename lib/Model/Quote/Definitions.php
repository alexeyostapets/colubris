<?php
class Model_Quote_Definitions extends Model_Auditable {
    public $table="quote";
    function init(){
        parent::init(); //$this->debug();
        if ($this->api->currentUser()->isClient()){
            $this->hasOne('Project_Client')->display(array('form'=>'Form_Field_AutoEmpty'))->mandatory('required');
        }elseif($this->api->currentUser()->isDeveloper()){
            $this->hasOne('Project_Participant')->display(array('form'=>'Form_Field_AutoEmpty'))->mandatory('required');
        }else{
            $this->hasOne('Project')->display(array('form'=>'Form_Field_AutoEmpty'))->mandatory('required');
        }

        //$this->addField('project_id')->refModel('Model_Project');
        //->display(array('form'=>'autocomplete/basic'));
        $this->hasOne('User');
        $this->addField('name')->mandatory('required');
        $this->addField('general_description')->type('text')->allowHtml(true);
        $this->addField('amount')->type('money')->mandatory(true);
        $this->addField('issued')->type('date');

        $this->addField('duration')->type('int');
        $this->addField('deadline')->type('date')->caption('Duration/Deadline');

        $this->addExpression('durdead')->caption('Duration(days)/Deadline')->set(function($m,$q){
            return $q->dsql()
                ->expr('if(deadline is null,duration,deadline)');
        });

        $this->addField('html')->type('text')->allowHtml(true);

        $this->addField('status')->setValueList(
            array(
                'quotation_requested'=>'Quotation Requested',
                'estimate_needed'=>'Estimate Needed',
                'not_estimated'=>'Not Estimated',
                'estimated'=>'Estimated',
                'estimation_approved'=>'Estimation Approved',
                'finished'=>'Finished',
            )
        )->mandatory('Cannot be empty')->sortable(true);
        //$this->addField('attachment_id')->setModel('Model_Filestore_File');

        $this->addField('rate')->defaultValue('0.00');
        $this->addField('currency')->setValueList(
            array(
                'GBP'=>'GBP',
                'EUR'=>'EUR',
                'USD'=>'USD',
            )
        )->mandatory('Cannot be empty');

        $this->addField('is_deleted')->type('boolean')->defaultValue('0');
        $this->addField('deleted_id')->refModel('Model_User');
        $this->addHook('beforeDelete', function($m){
            $m['deleted_id']=$m->api->currentUser()->get('id');
        });

        $this->addField('organisation_id')->refModel('Model_Organisation');

        $this->addField('created_dts');
        $this->addField('updated_dts')->caption('Updated')->sortable(true);

        $this->addField('expires_dts')->caption('Expires');

        $this->addField('is_archived')->type('boolean')->defaultValue('0');

        $this->addField('warranty_end')->type('date')->caption('Warranty end');

        $this->addField('show_time_to_client')->type('boolean')->defaultValue('0');

        $this->addHook('beforeInsert', function($m,$q){
            $q->set('created_dts', $q->expr('now()'));
            $q->set('expires_dts', $q->expr('DATE_ADD(NOW(), INTERVAL 1 MONTH)'));
        });

        $this->addHook('beforeSave', function($m){
            $m['updated_dts']=date('Y-m-d G:i:s', time());
            if($m['status']=='finished') $m['warranty_end']=date('Y-m-d G:i:s', time()+60*60*24*30);
        });

        $this->addExpression('client_id')->set(function($m,$q){
            return $q->dsql()
                ->table('project')
                ->field('client_id')
                ->where('project.id',$q->getField('project_id'))
                ;
        });

        $this->addExpression('estimated')->caption('Est.time(hours)')->set(function($m,$q){
            return $q->dsql()
                ->table('requirement')
                ->field('sum(estimate)')
                ->where('requirement.quote_id',$q->getField('id'))
                ->where('requirement.is_included','1')
                ->where('requirement.is_deleted','0')
                ;
        });

        $this->addExpression('calc_rate')->caption('Rate')->set(function($m,$q){
            return 'IF( rate
                    ,
                        rate
                    ,
                        IF(
                            (SELECT value FROM rate WHERE
                                `from`<=(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                                AND
                                `to`>(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                            )
                        ,
                            (SELECT value FROM rate WHERE
                                `from`<=(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                                AND
                                `to`>(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                            )
                        ,
                        ""
                        )
                    )
                    ';
        });

        $this->addExpression('estimpay')->caption('Est.pay')->set(function($m,$q){
            return 'IF(
                        (SELECT SUM(requirement.estimate)*quote.rate
                        FROM requirement
                        WHERE requirement.quote_id=quote.id
                            AND requirement.is_included=1)
                    ,
                        (SELECT SUM(requirement.estimate)*quote.rate
                        FROM requirement
                        WHERE requirement.quote_id=quote.id
                            AND requirement.is_included=1)
                    ,   IF(
                            (SELECT SUM(requirement.estimate)*
                                (SELECT value FROM rate WHERE
                                `from`<=(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                                AND
                                `to`>(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                            )
                                FROM requirement
                                WHERE requirement.quote_id=quote.id
                                    AND requirement.is_included=1)
                        ,
                            (SELECT SUM(requirement.estimate)*
                                (SELECT value FROM rate WHERE
                                `from`<=(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                                AND
                                `to`>(SELECT SUM(requirement.estimate)
                                    FROM requirement
                                    WHERE requirement.quote_id=quote.id
                                        AND requirement.is_included=1
                                )
                            )
                                FROM requirement
                                WHERE requirement.quote_id=quote.id
                                    AND requirement.is_included=1)
                        ,
                        ""
                        )
                    )
                    ';
            /*
            return $q->dsql()
                ->table('requirement')
                ->field('sum(estimate)*'.$q->getField('rate'))
                ->where('requirement.quote_id',$q->getField('id'))
                ->where('requirement.is_included','1')
                ;
            */
        });

        $this->addExpression('spent_time')->set(function($m,$q){
            return $q->dsql()
                ->table('task')
                ->table('task_time')
                ->table('requirement')
                ->field('sum(task_time.spent_time)')
                ->where('requirement.id=task.requirement_id')
                ->where('task.id=task_time.task_id')
                ->where('requirement.quote_id',$q->getField('id'))
                ->where('remove_billing',0)
                ;
        });

    }

    function getRequirements(){
        $rm=$this->add('Model_Requirement')->addCondition('quote_id',$this->get('id'));
        return($rm->getRows());
    }
    function getRequirements_id(){
        $rids='';
        foreach($this->getRequirements() as $reqs){
            if ($rids=='') $rids=$reqs['id']; else $rids.=','.$reqs['id'];
        }

        return($rids);
    }

}

class Form_Field_AutoEmpty extends autocomplete\Form_Field_Basic {
    public $min_length = -1;
    public $hint = 'Ckick to see list of projects. Search results will be limited to 20 records.';
    function init(){
        parent::init();
        $this->other_field->js('click',array(
                $this->other_field->js()->autocomplete( "search", "" ),
            )
        );
    }
}
