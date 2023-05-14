<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib\form;

use Closure;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function is_null;

class FormInterface implements Form
{

	/** @var array $data */
	protected array $data = [];
	/** @var Closure $xButtonCallback */
	private ?Closure $xButtonCallback = null;
	/** @var Closure[] $onUse */
	protected array $onUseList = [];

	/**
	 * @param null|Closure $xButtonCallback
	 */
	public function __construct(?callable $xButtonCallback = null)
	{
		$this->xButtonCallback = $xButtonCallback;
	}

	/**
	 * @param Player $player
	 * @param mixed $data
	 * @param 
	 */
	public function handleResponse(Player $player, $data): void
	{
		if ($data === null && !is_null($this->xButtonCallback)) {
			($this->xButtonCallback)($player);
			return;
		}

		if (count($this->onUseList) > 0) {
			($this->onUseList[$data])($player, $data);
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->data;
	}
}
