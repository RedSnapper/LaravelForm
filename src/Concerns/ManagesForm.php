<?php

namespace RS\Form\Concerns;

use RS\Form\Fields\Hidden;

trait ManagesForm {


	/**
	 * Form attributes
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Hidden fields to be rendered by the form
	 *
	 * @var array
	 */

	protected $hidden = [];

	/**
	 * The reserved form open attributes.
	 *
	 * @var array
	 */
	protected $reserved = ['method', 'url', 'route', 'action', 'files'];

	/**
	 * The form methods that should be spoofed, in uppercase.
	 *
	 * @var array
	 */
	protected $spoofedMethods = ['DELETE', 'PATCH', 'PUT'];


	public function create(array $options = []) {

		$method = array_get($options, 'method', 'post');

		$attributes['method'] = $this->getMethod($method);

		$attributes['action'] = $this->getAction($options);

		// If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
		// field that will instruct the Symfony request to pretend the method is a
		// different method than it actually is, for convenience from the forms.
		$this->getAppendage($method);

		$this->attributes = array_merge(
		  $attributes, array_except($options, $this->reserved)
		);

		return $this;
	}

	/**
	 * Parse the form action method.
	 *
	 * @param  string $method
	 * @return string
	 */
	protected function getMethod(string $method): string {
		$method = strtolower($method);
		return $method != 'get' ? 'post' : $method;
	}

	/**
	 * Get the form action from the options.
	 *
	 * @param  array $options
	 * @return string
	 */
	protected function getAction(array $options) {

		// We will also check for a "route" or "action" parameter on the array so that
		// developers can easily specify a route or controller action when creating
		// a form providing a convenient interface for creating the form actions.
		if (isset($options['url'])) {
			return $this->getUrlAction($options['url']);
		}

		if (isset($options['route'])) {
			return $this->getRouteAction($options['route']);
		}

		// If an action is available, we are attempting to open a form to a controller
		// action route. So, we will use the URL generator to get the path to these
		// actions and return them from the method. Otherwise, we'll use current.
		elseif (isset($options['action'])) {
			return $this->getControllerAction($options['action']);
		}
		return $this->url->current();
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param  array|string $options
	 * @return string
	 */
	protected function getUrlAction($options) {
		if (is_array($options)) {
			return $this->url->to($options[0], array_slice($options, 1));
		}
		return $this->url->to($options);
	}

	/**
	 * Get the action for a "route" option.
	 *
	 * @param  array|string $options
	 * @return string
	 */
	protected function getRouteAction($options) {
		if (is_array($options)) {
			return $this->url->route($options[0], array_slice($options, 1));
		}
		return $this->url->route($options);
	}

	/**
	 * Get the action for an "action" option.
	 *
	 * @param  array|string $options
	 * @return string
	 */
	protected function getControllerAction($options) {
		if (is_array($options)) {
			return $this->url->action($options[0], array_slice($options, 1));
		}
		return $this->url->action($options);
	}

	/**
	 * Get the form appendage for the given method.
	 *
	 * @param  string $method
	 */
	protected function getAppendage($method) {

		$method = strtoupper($method);
		// If the HTTP method is in this list of spoofed methods, we will attach the
		// method spoofer hidden input to the form. This allows us to use regular
		// form to initiate PUT and DELETE requests in addition to the typical.
		if (in_array($method, $this->spoofedMethods)) {
			$this->hidden [] = (new Hidden('_method'))->setValue($method);
		}

		// If the method is something other than GET we will go ahead and attach the
		// CSRF token to the form, as this can't hurt and is convenient to simply
		// always have available on every form the developers creates for them.
		if ($method != 'GET') {
			$this->token();
		}
	}

	protected function token() {
		$this->hidden[] = (new Hidden('_token'))->setValue($this->session->token());
	}
}