<?php


namespace app\index\controller;


use app\common\controller\HomeBase;
use app\common\model\AppStep;
use app\common\model\UserOrder;

class Step extends HomeBase
{
    private $steps;
    protected $stepModel;
    protected $order;
    protected $complete = false;
    public function __construct($app_id, $order)
    {
        $this->stepModel = new AppStep();
        $this->order = $order;
        $this->steps = $this->stepModel->columns(['app_id' => $app_id, 'status' => 1],'id');
    }

    public function stepEdit($step_id, $type = 'Complete')
    {
        $stepOk = explode(',', $this->order['step_ok']);
        $stepErr = explode(',', $this->order['step_error']);
        if ($type == 'Complete') {
            if (!in_array($step_id, $stepOk)) {
                array_push($stepOk, $step_id);
            }
            if (in_array($step_id, $stepErr)) {
                $key = array_search($step_id, $stepErr);
                unset($stepErr[$key]);
            }
        } else if ( $type = 'Incomplete'){
            if (!in_array($step_id, $stepErr)) {
                array_push( $stepErr,$step_id);
            }
            if (in_array($step_id, $stepOk)) {
                $key = array_search($step_id, $stepOk);
                unset($stepOk[$key]);
            }
        }else {
            return [];
        }
        $steps = $this->steps;
        $diff = array_diff($steps,$stepOk);
        if (empty($diff)){
            $this->complete = true;
        }
        $stepOk = trim(implode(',',$stepOk),',');
        $stepErr = trim(implode(',',$stepErr),',');
        $data = [
            'step_ok' => $stepOk,
            'step_error'=>$stepErr
        ];
        return $data;
    }

    public function isComplete()
    {
       return $this->complete;
    }
}