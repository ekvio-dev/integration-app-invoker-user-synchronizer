<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\Extractor;
use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use Ekvio\Integration\Invoker\UserFactory\UserFactory;
use Ekvio\Integration\Invoker\UserValidation\UserValidator;
use Ekvio\Integration\Sdk\V2\User\UserSync;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

/**
 * Class UserSynchronizer
 * @package App
 */
class UserSynchronizer implements Invoker
{
    private const NAME = 'Synchronize users';
    /**
     * @var Extractor
     */
    private $userExtractor;
    /**
     * @var FilesystemInterface
     */
    private $fs;
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
     * @param Extractor $userExtractor
     * @param FilesystemInterface $fs
     * @param UserFactory $userFactory
     * @param UserValidator $validator
     * @param UserSync $userSync
     * @param Profiler $profiler
     */
    public function __construct(
        Extractor $userExtractor,
        FilesystemInterface $fs,
        UserFactory $userFactory,
        UserValidator $validator,
        UserSync $userSync,
        Profiler $profiler)
    {
        $this->userExtractor = $userExtractor;
        $this->fs = $fs;
        $this->userFactory = $userFactory;
        $this->validator = $validator;
        $this->equeoUserApi = $userSync;
        $this->profiler = $profiler;
    }
    /**
     * @inheritDoc
     */
    public function __invoke(array $parameters = []): void
    {
        $sync = $parameters['sync'];

        $this->profiler->profile('Extract users...');
        $users = $this->userExtractor->extract();
        $this->profiler->profile(sprintf('Extracted %s users...', count($users)));

        $this->profiler->profile('Building users from extracted data');
        $users = $this->userFactory->build($users);
        $this->profiler->profile(sprintf('Built %s users', count($users)));

        $this->profiler->profile(sprintf('Validating %s users....', count($users)));
        $validationResult = $this->validator->validate($users);

        $errors = $validationResult->errors();
        $this->profiler->profile(sprintf('Collecting %s validation errors...', count($errors)));

        $users = $validationResult->valid();
        $this->profiler->profile(sprintf('%s valid users...', count($users)));

        $syncResult = [];
        if($users) {
            $this->profiler->profile(sprintf('Synchronize %s users...', count($users)));
            $syncResult = $this->equeoUserApi->sync($users);
            $this->profiler->profile(sprintf('Get %s user logs...', count($syncResult)));
        }

        $dataCollector = array_merge($errors, $syncResult);

        $this->profiler->profile(sprintf('Saving sync result to %s', $errors));
        if($this->fs->put($sync, json_encode($dataCollector)) === false) {
            throw new RuntimeException(sprintf('Failed saving file %s', $errors));
        }
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return self::NAME;
    }
}