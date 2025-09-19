<?php

namespace Gravity_Flow\Gravity_Flow\Inbox\Endpoints\Get_Inbox_Items;

use Gravity_Flow\Gravity_Flow\Ajax\Argument;
use Gravity_Flow\Gravity_Flow\Ajax\Config as Ajax_Config;

/**
 * Get_Inbox_Items AJAX Config
 *
 * @since 2.8
 */
class Config extends Ajax_Config {

	const SEARCH_ARGS = 'search_args';
	const TOKEN       = 'gflow_access_token';

	/**
	 * Name
	 *
	 * @var string
	 */
	protected $name = 'inbox/entries';

	/**
	 * The arguments for the endpoint.
	 *
	 * @return array
	 */
	public function args() {
		return array(
			$this->search_args(),
			$this->token_args(),
		);
	}

	/**
	 * The search argument.
	 *
	 * @since 2.8
	 *
	 * @return Argument
	 */
	public function search_args() {
		return new Argument(
			self::SEARCH_ARGS,
			false,
			array()
		);
	}

	/**
	 * The token argument.
	 *
	 * @since 2.8
	 *
	 * @return Argument
	 */
	public function token_args() {
		return new Argument(
			self::TOKEN,
			true
		);
	}
}