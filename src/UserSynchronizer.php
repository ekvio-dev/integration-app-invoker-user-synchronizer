<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\Collector;
use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Contracts\User\UserPipelineData;
use Ekvio\Integration\Invoker\UserFactory\UserFactory;
use Ekvio\Integration\Invoker\UserValidation\UserValidator;
use Ekvio\Integration\Sdk\V2\User\UserSync;

/**
 * Class UserSynchronizer
 * @package App
 */
class UserSynchronizer implements Invoker
{
    private const NAME = 'Synchronize users';
    /**
     * @var Collector
     */
    private $userCollector;
    /**
     * @var UserFactory
     */
    private $userFactory;
    /**
     * @var UserValidator
     */
    private $validator;
    /**
     * @var UserSync
     */
    private $equeoUserApi;
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * UserSynchronizer constructor.
     * @param Collector $userCollector
     * @param UserFactory $userFactory
     * @param UserValidator $validator
     * @param UserSync $userSync
     * @param Profiler $profiler
     */
    public function __construct(
        Collector $userCollector,
        UserFactory $userFactory,
        UserValidator $validator,
        UserSync $userSync,
        Profiler $profiler)
    {
        $this->userCollector = $userCollector;
        $this->userFactory = $userFactory;
        $this->validator = $validator;
        $this->equeoUserApi = $userSync;
        $this->profiler = $profiler;
    }
    /**
     * @inheritDoc
     */
    public function __invoke(array $arguments = [])
    {
        $this->profiler->profile('Begin collecting data...');
        /** @var UserPipelineData $userSyncPipelineData */
        $userSyncPipelineData = $this->userCollector->collect();
        $this->profiler->profile(sprintf('Collecting %s users...', count($userSyncPipelineData->data())));

        $this->profiler->profile('Building users from extracted data');
        $userSyncPipelineData = $this->userFactory->build($userSyncPipelineData);
        $this->profiler->profile(sprintf('Built %s users', count($userSyncPipelineData->data())));

        $this->profiler->profile(sprintf('Validating %s users....', count($userSyncPipelineData->data())));
        $userSyncPipelineData = $this->validator->validate($userSyncPipelineData);
        $this->profiler->profile(sprintf('Valid %s users...', count($userSyncPipelineData->data())));

        $userSyncPipelineData = $this->syncUsers($userSyncPipelineData);
        $this->profiler->profile(sprintf('Summary %s logs...', count($userSyncPipelineData->logs())));

        return $userSyncPipelineData;
    }

    /**
     * @param UserPipelineData $pipelineData
     * @return UserPipelineData
     */
    private function syncUsers(UserPipelineData $pipelineData): UserPipelineData
    {
        if($pipelineData->data()) {
            $this->profiler->profile(sprintf('Synchronize %s users...', count($pipelineData->data())));

            $userDataKeyMap = [];
            $users = [];
            foreach ($pipelineData->data() as $index => $userData) {
                /** @var UserSyncData $userData */
                $userDataKeyMap[$index] = $userData->key();
                $users[] = $userData->data();
            }

            $syncResults = $this->equeoUserApi->sync($users);
            $this->profiler->profile(sprintf('Get %s logs from Equeo...', count($syncResults)));

            foreach ($syncResults as $result) {
                if(isset($result['index']) && is_numeric($result['index'])) {
                    $result['index'] = $userDataKeyMap[$result['index']];
                }

                $pipelineData->addLog($result);
            }
        }

        return $pipelineData;
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return self::NAME;
    }
}