<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework;

use BadMethodCallException;

trait AttachDaftFramework
{
	protected ? Framework $daftFrameworkInstance = null;

	public function AttachDaftFramework(Framework $framework) : void
	{
		if ($this->daftFrameworkInstance instanceof Framework) {
			throw new BadMethodCallException(
				'Framework must not be attached if a framework is already attached!'
			);
		}

		$this->daftFrameworkInstance = $framework;
	}

	public function DetachDaftFramework() : ? Framework
	{
		$out = $this->daftFrameworkInstance;

		if ($out instanceof Framework) {
			$this->daftFrameworkInstance = null;
		}

		return $out;
	}

	public function GetDaftFramework() : ? Framework
	{
		return $this->daftFrameworkInstance;
	}

	public function CheckIfUsingFrameworkInstance(
		Framework ...$instances
	) : bool {
		return in_array($this->daftFrameworkInstance, $instances, true);
	}
}
