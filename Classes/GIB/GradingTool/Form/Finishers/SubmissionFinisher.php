<?php
namespace GIB\GradingTool\Form\Finishers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Form".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This finisher sends an email to one recipient
 *
 */

use TYPO3\Flow\Annotations as Flow;

class SubmissionFinisher extends \TYPO3\Form\Core\Model\AbstractFinisher {

	/**
	 * @var array
	 */
	protected $defaultOptions = array();

	/**
	 * @var \GIB\GradingTool\Domain\Repository\ProjectRepository
	 * @Flow\Inject
	 */
	protected $projectRepository;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * The flash messages. Use $this->flashMessageContainer->addMessage(...) to add a new Flash
	 * Message.
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * Executes this finisher
	 * @see AbstractFinisher::execute()
	 *
	 * @return void
	 * @throws \TYPO3\Form\Exception\FinisherException
	 */
	protected function executeInternal() {

		/** @var \TYPO3\Form\Core\Runtime\FormRuntime $formRuntime */
		$formRuntime = $this->finisherContext->getFormRuntime();

		// The corresponding project
		$projectIdentifier = $formRuntime->getRequest()->getParentRequest()->getArgument('project')['__identity'];
		/** @var \GIB\GradingTool\Domain\Model\Project $project */
		$project = $this->projectRepository->findByIdentifier($projectIdentifier);

		// update the project with the data from the form
		$formValueArray = $formRuntime->getFormState()->getFormValues();
		$project->setSubmissionContent(serialize($formValueArray));
		$project->setLastUpdated(new \TYPO3\Flow\Utility\Now);
		$project->setLastUpdated(new \TYPO3\Flow\Utility\Now);
		$this->projectRepository->update($project);

		$this->persistenceManager->persistAll();

		// add a flash message
		$message = new \TYPO3\Flow\Error\Message('Your submission for project "%s" was successfully saved.', \TYPO3\Flow\Error\Message::SEVERITY_OK, array($project->getProjectTitle()));
		$this->flashMessageContainer->addMessage($message);

		// redirect to dashboard
		$formRuntime = $this->finisherContext->getFormRuntime();
		$request = $formRuntime->getRequest()->getMainRequest();

		$uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($request);
		$uriBuilder->reset();
		$uri = $uriBuilder->uriFor('index', NULL, 'Standard');
		$uri = $request->getHttpRequest()->getBaseUri() . $uri;

		$response = $formRuntime->getResponse();
		$mainResponse = $response;
		while ($response = $response->getParentResponse()) {
			$mainResponse = $response;
		};
		$mainResponse->setStatus(303);
		$mainResponse->setHeader('Location', (string)$uri);

	}

}