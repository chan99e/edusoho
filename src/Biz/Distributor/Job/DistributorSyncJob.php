<?php

namespace Biz\Distributor\Job;

use Codeages\Biz\Framework\Scheduler\AbstractJob;
use Biz\Distributor\Util\DistributorJobStatus;

class DistributorSyncJob extends AbstractJob
{
    private $distributorServices = array();

    public function execute()
    {
        $drpService = $this->getDrpService();
        if (!empty($drpService)) {
            $distributorServices = $this->getDistributorServiceList();

            foreach ($distributorServices as $service) {
                $jobData = $service->findJobData();
                if (!empty($jobData)) {
                    $status = DistributorJobStatus::$ERROR;
                    try {
                        $result = $drpService->postData($jobData, $service->getSendType());
                        $resultJson = json_encode($result);

                        if ('success' == $resultJson['code']) {
                            $status = DistributorJobStatus::$FINISHED;
                        }
                        $this->biz['logger']->info(
                            'distributor send job DistributorSyncJob::execute ',
                            array('jobData' => $jobData, 'result' => empty($result) ? '' : $result)
                        );
                    } catch (\Exception $e) {
                        $this->biz['logger']->error(
                            'distributor send job error DistributorSyncJob::execute '.$e->getMessage(),
                            array('jobData' => $jobData, 'result' => empty($result) ? '' : $result, 'trace' => $e->getTraceAsString())
                        );
                    }

                    $service->batchUpdateStatus($jobData, $status);
                }
            }
        }
    }

    protected function getDistributorService()
    {
        $args = $this->__get('args');

        return $this->biz->service('Distributor:Distributor'.$args['type'].'Service');
    }

    private function getDistributorServiceList()
    {
        if (empty($this->distributorServices)) {
            $this->distributorServices = array();
            $types = array('User', 'Order');
            foreach ($types as $type) {
                array_push($this->distributorServices, $this->biz->service('Distributor:Distributor'.$type.'Service'));
            }
        }

        return $this->distributorServices;
    }

    protected function getDrpService()
    {
        $distributorServices = $this->getDistributorServiceList();

        return $distributorServices[0]->getDrpService();
    }
}
