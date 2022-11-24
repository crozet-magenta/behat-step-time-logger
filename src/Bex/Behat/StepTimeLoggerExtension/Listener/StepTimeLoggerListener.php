<?php

namespace Bex\Behat\StepTimeLoggerExtension\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Hook\BeforeFeature;
use Behat\Hook\BeforeScenario;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Bex\Behat\StepTimeLoggerExtension\Service\FeatureTimeLogger;
use Bex\Behat\StepTimeLoggerExtension\ServiceContainer\Config;
use Bex\Behat\StepTimeLoggerExtension\Service\StepTimeLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StepTimeLoggerListener implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StepTimeLogger
     */
    private $stepTimeLogger;

    /**
     * @var FeatureTimeLogger
     */
    private $featureTimeLogger;

    /**
     * @param Config         $config
     * @param StepTimeLogger $stepTimeLogger
     * @param FeatureTimeLogger $featureTimeLogger
     */
    public function __construct(Config $config, StepTimeLogger $stepTimeLogger, FeatureTimeLogger $featureTimeLogger)
    {
        $this->config = $config;
        $this->stepTimeLogger = $stepTimeLogger;
        $this->featureTimeLogger = $featureTimeLogger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StepTested::BEFORE => 'stepStarted',
            StepTested::AFTER => 'stepFinished',
            SuiteTested::AFTER => 'suiteFinished',
            FeatureTested::BEFORE => 'featureStarted',
            FeatureTested::AFTER => 'featureEnded',
        ];
    }

    /**
     * @param BeforeStepTested $event
     */
    public function stepStarted(BeforeStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->stepTimeLogger->logStepStarted(substr(preg_replace('/".*"/U', '...', $event->getStep()->getText()), 0, 100));
        }
    }

    /**
     * @param AfterStepTested $event
     */
    public function stepFinished(AfterStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->stepTimeLogger->logStepFinished(substr(preg_replace('/".*"/U', '...', $event->getStep()->getText()), 0, 100));
        }
    }

    public function featureStarted(BeforeFeatureTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->featureTimeLogger->logStepStarted($event->getFeature()->getFile());
        }
    }

    public function featureEnded(AfterFeatureTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->featureTimeLogger->logStepFinished($event->getFeature()->getFile());
        }
    }

    /**
     * @return void
     */
    public function suiteFinished()
    {
        if ($this->config->isEnabled()) {
            foreach ($this->config->getOutputPrinters() as $printer) {
                $printer->printLogs($this->stepTimeLogger->executionInformationGenerator());
                $printer->printLogs($this->featureTimeLogger->executionInformationGenerator());
            }

            $this->stepTimeLogger->clearLogs();
            $this->featureTimeLogger->clearLogs();
        }
    }
}
