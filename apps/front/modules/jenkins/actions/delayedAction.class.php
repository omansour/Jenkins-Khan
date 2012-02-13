<?php

class delayedAction extends baseJenkinsAction
{

  /**
   * @param sfRequest $request The current sfRequest object
   *
   * @return mixed     A string containing the view name associated with this action
   */
  function execute($request)
  {
    $runs = JenkinsRunPeer::getDelayed($this->getUser());
    $form = new DelayedRunForm(array(), array('runs' => $runs));
    $jenkins = $this->getJenkins();

    if (sfRequest::POST === $request->getMethod())
    {
      $form->bind($request->getParameter('delayed_run'));

      if ($form->isValid())
      {
        foreach ($form->getValue('runs') as $id => $selected)
        {
          if ('on' !== $selected)
          {
            continue;
          }
          
          $run = JenkinsRunPeer::retrieveByPK($id);
          
          $parameters  = array(
            Jenkins_Job::BRANCH_PARAMETER_NAME => $run->getGitBranch()
          );
          $runParameters = $run->decodeParameters();
          if (is_array($runParameters))
          {
            $parameters = array_merge($parameters, $runParameters);
          }
          
          $jenkins->launchJob($run->getJobName(), $parameters);
          $run->setLaunched(true);
          $run->save();
        }
        
        $this->redirect('jenkins/index');
      }
    }
    
    $delayedRuns = array();
    foreach ($runs as $run)
    {
      $groupRun = $run->getJenkinsGroupRun();
      $parameters = $run->getParameters();
      
      $delayedRuns[$run->getId()] = array(
        'group_run_label' => $groupRun->getLabel(),
        'group_run_result' => $groupRun->getResult($jenkins),
        'parameters' =>  null === $parameters ? $parameters : json_decode($run->getParameters(), true),
      );
      
    }
    
    $this->setVar('form', $form);
    $this->setVar('delayed_runs', $delayedRuns);
  }
}