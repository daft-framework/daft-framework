<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console;

use BadMethodCallException;
use SignpostMarv\DaftFramework\AttachDaftFramework;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;
use SignpostMarv\DaftInterfaceCollector\StaticMethodCollector;
use Symfony\Component\Console\Application as Base;
use Symfony\Component\Console\Command\Command as BaseCommand;

class Application extends Base
{
	use AttachDaftFramework;

	/**
	 * @return BaseCommand|null
	 */
	public function add(BaseCommand $command)
	{
		return $this->addStrict($command);
	}

	public function addStrict(BaseCommand $command) : ? BaseCommand
	{
		$out = parent::add($command);

		if ($out instanceof Command) {
			$maybeFramework = $this->GetDaftFramework();

			if ( ! ($maybeFramework instanceof Framework)) {
				throw new BadMethodCallException(
					'Cannot add a daft framework command without a framework being attached!'
				);
			}

			$out->AttachDaftFramework($maybeFramework);
		} elseif ($command instanceof Command) {
			$command->DetachDaftFramework();
		}

		return $out;
	}

	public function GetCommandCollector() : StaticMethodCollector
	{
		return new StaticMethodCollector(
			[
				DaftConsoleSource::class => [
					'DaftFrameworkConsoleSources' => [
						DaftConsoleSource::class,
						BaseCommand::class,
						Command::class,
					],
				],
			],
			[
				BaseCommand::class,
				Command::class,
			]
		);
	}

	/**
	 * @param class-string ...$sources
	 */
	public function CollectCommands(string ...$sources) : void
	{
		$framework = $this->GetDaftFramework();

		if ( ! ($framework instanceof Framework)) {
			throw new BadMethodCallException(
				'Cannot collect commands without an attached framework instance!'
			);
		}

		/**
		 * @var iterable<class-string<BaseCommand>>
		 */
		$implementations = $this->GetCommandCollector()->Collect(...$sources);

		foreach ($implementations as $implementation) {
			$command = new $implementation($implementation::getDefaultName());

			$this->add($command);
		}
	}

	/**
	 * @return static
	 */
	public static function CollectApplicationWithCommands(
		string $name,
		string $version,
		Framework $framework
	) : self {
		$application = new static($name, $version);
		$application->AttachDaftFramework($framework);

		/**
		 * @var array<int, class-string>
		 */
		$sources = (array) (
			$framework->ObtainConfig()[DaftConsoleSource::class] ?? []
		);

		$application->CollectCommands(...$sources);

		return $application;
	}
}
