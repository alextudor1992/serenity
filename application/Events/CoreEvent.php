<?php

namespace Serenity\Application\Events;

abstract class CoreEvent {
	public const INIT = 'init';
	public const REQUEST_END = 'request_end';
	public const REQUEST_PRE_END = 'pre_request_end';
}