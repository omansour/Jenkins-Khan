<?php



/**
 * Skeleton subclass for representing a row from the 'jenkins_run' table.
 *
 *
 *
 * This class was autogenerated by Propel 1.6.3 on:
 *
 * Fri Jan 20 17:32:29 2012
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.lib.model
 */
class JenkinsRun extends BaseJenkinsRun
{

  /**
   * @var string
   */
  const FAILURE = 'FAILURE';

  /**
   * @var string
   */
  const SUCCESS = 'SUCCESS';

  /**
   * @var string
   */
  const RUNNING = 'RUNNING';

  /**
   * @var string
   */
  const WAITING = 'WAITING';

  /**
   * @var string
   */
  const UNREACHABLE = 'UNREACHABLE';

  /**
   * @var string
   */
  const UNSTABLE = 'UNSTABLE';

  /**
   * @var string
   */
  const ABORTED = 'ABORTED';

  /**
   * @var string
   */
  const DELAYED = 'DELAYED';

  /**
   * @param Jenkins $jenkins
   * @param string  $defaultStatus
   *
   * @return string
   */
  public function getJenkinsResult(Jenkins $jenkins, $defaultStatus = JenkinsRun::UNREACHABLE)
  {
    $result = $defaultStatus;
    $build  = null;

    if ($jenkins->isAvailable())
    {
      if ($this->isInJenkinsQueue($jenkins))
      {
        return JenkinsRun::WAITING;
      }

      $build = $this->getJenkinsBuild($jenkins);

      if (null !== $build)
      {

        $result = $build->getResult();
        if ($result != JenkinsRun::RUNNING && !$this->getLaunched())
        {
          return JenkinsRun::DELAYED;
        }
        return $result;
      }
    }

    if (!$this->getLaunched())
    {
      return JenkinsRun::DELAYED;
    }

    return $result;
  }

  /**
   * @param Jenkins $jenkins
   * @param string  $format
   *
   * @return null|string
   */
  public function getStartTime(Jenkins $jenkins, $format = 'd/m/Y H:i:s')
  {
    $build = $this->getJenkinsBuild($jenkins);

    $date = null;
    if (null !== $build)
    {
      $date = date($format, $build->getTimestamp());
    }

    return $date;
  }

  /**
   * @param Jenkins $jenkins
   *
   * @return int|null
   */
  public function getDuration(Jenkins $jenkins)
  {
    $build    = $this->getJenkinsBuild($jenkins);
    $duration = null;
    if (null !== $build)
    {
      $duration = $build->getDuration();

      if (0 === $duration)
      {
        $duration = null;
      }
    }

    return $duration;
  }


  /**
   * @param Jenkins $jenkins
   *
   * @return array
   */
  public function getJenkinsBuildCleanedParameter(Jenkins $jenkins)
  {
    $build      = $this->getJenkinsBuild($jenkins);
    $parameters = array();
    if (null !== $build)
    {
      $parameters = $build->getInputParameters();
      unset($parameters[Jenkins_Job::BRANCH_PARAMETER_NAME]);
    }

    return $parameters;
  }

  /**
   * @param Jenkins $jenkins
   *
   * @return bool
   */
  public function isInJenkinsQueue(Jenkins $jenkins)
  {
    return (null !== $this->getJenkinsQueue($jenkins));
  }


  /**
   * @param Jenkins $jenkins
   *
   * @return Jenkins_JobQueue|null
   */
  public function getJenkinsQueue(Jenkins $jenkins)
  {
    $jenkinsQueue = $jenkins->getQueue();
    foreach ($jenkinsQueue->getJobQueues() as $jobQueue)
    {
      /** @var Jenkins_JobQueue $jobQueue  */
      if ($this->isRelatedToJenkinsJobQueue($jobQueue))
      {
        return $jobQueue;
      }
    }

    return null;
  }


  /**
   * @param Jenkins $jenkins
   *
   * @return \Jenkins_Build
   */
  public function getJenkinsBuild(Jenkins $jenkins)
  {
    $build = null;
    if (null !== $this->getJobBuildNumber() && $jenkins->isAvailable())
    {
      return $build = $jenkins->getBuild($this->getJobName(), $this->getJobBuildNumber());
    }

    return $build;
  }

  public function computeJobBuildNumber(Jenkins $jenkins, myUser $user)
  {
    if (null !== $this->getJobBuildNumber())
    {
      return;
    }

    $criteria = new Criteria();
    $criteria->add(JenkinsRunPeer::ID, $this->getId());
    JenkinsRunPeer::updateJobBuildNumber($jenkins, $user, $criteria);
  }

  /**
   * @param Jenkins $jenkins
   *
   * @return null|string
   */
  public function getUrlBuild(Jenkins $jenkins)
  {
    return $jenkins->getUrlBuild($this->getJobName(), $this->getJobBuildNumber());
  }

  /**
   * @return null
   */
  public function getUrlCancel()
  {
    return null;
  }

  /**
   * @param array $parameters
   */
  public function encodeParameters($parameters)
  {
    if (is_array($parameters) && count($parameters) > 0)
    {
      $this->setParameters(json_encode($parameters));
    }
  }

  /**
   * @return null|string.
   */
  public function decodeParameters()
  {
    if (null !== $parameters = $this->getParameters())
    {
      $parameters = json_decode($parameters, true);
    }

    return $parameters;
  }


  /**
   * @param \Jenkins_JobQueue $queue
   *
   * @return boolean
   */
  public function isRelatedToJenkinsJobQueue(Jenkins_JobQueue $queue)
  {
    if ($queue->getJobName() != $this->getJobName())
    {
      return false;
    }

    return $this->checkRelationWithParameters($queue->getInputParameters());
  }

  /**
   * @param Jenkins_Build $build
   *
   * @return boolean
   */
  public function isRelatedToJenkinsBuild(Jenkins_Build $build)
  {
    return $this->checkRelationWithParameters($build->getInputParameters());
  }

  /**
   * @param array $parameters
   *
   * @return boolean
   */
  private function checkRelationWithParameters(array $parameters)
  {
    //verifier la branche et les paramètres
    if ($parameters[Jenkins_Job::BRANCH_PARAMETER_NAME] != $this->getGitBranch())
    {
      return false;
    }

    $runParameters = $this->decodeParameters();
    
    if (is_array($runParameters))
    {
      foreach ($runParameters as $key => $value)
      {
        if (!isset($parameters[$key]))
        {
          //pas le parametre ==> FAIL
          return false;
        }

        if ($parameters[$key] != $value)
        {
          //pas la même valeur
          return false;
        }
      }
    }

    //si on survit à toutes ces conditions, c'est qu'on a trouvé le bon build/queue
    return true;
  }

  /**
   * @param Jenkins $jenkins
   * @param array   $parameters
   */
  public function launch(Jenkins $jenkins, $parameters = array())
  {
    $jenkins->launchJob($this->getJobName(), array_merge(
      $parameters,
      array(
        Jenkins_Job::BRANCH_PARAMETER_NAME => $this->getGitBranch()
      )
    ));
  }

  /**
   * @return bool
   */
  public function isRebuildable()
  {
    return $this->getLaunched();
  }

  /**
   * @param Jenkins $jenkins
   * @param bool    $delayed
   *
   * @return $this
   */
  public function rebuild(Jenkins $jenkins, $delayed = false)
  {
    $build           = $this->getJenkinsBuild($jenkins);
    $inputParameters = array();
    if (null !== $build)
    {
      //peu importe ce qui est stocké dans la base => Jenkins fait toujours foi
      $inputParameters = $build->getInputParameters();
    }

    if ($delayed)
    {
      $this->setLaunched(false);
    }

    $this->save();

    if (!$delayed)
    {
      $this->launch($jenkins, $inputParameters);
    }


    return $this;
  }

  /**
   * @param Jenkins $jenkins
   *
   * @return JenkinsRun
   */
  public function launchDelayed(Jenkins $jenkins)
  {
    $parameters  = array(
      Jenkins_Job::BRANCH_PARAMETER_NAME => $this->getGitBranch()
    );
    $runParameters = $this->decodeParameters();
    if (is_array($runParameters))
    {
      $parameters = array_merge($parameters, $runParameters);
    }

    $this->launch($jenkins, $parameters);
    $this->setLaunched(true);
    $this->save();

    return $this;
  }

} // JenkinsRun
