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

namespace vp817\GameLib\util;

use vp817\GameLib\traits\ArenaPlayerTrait;

final class Team
{
	use ArenaPlayerTrait {
		add as public addPlayer;
		remove as public removePlayer;
		get as public getPlayer;
		has as public hasPlayer;
		getAll as public getPlayers;
		add as private add;
		remove as private remove;
		get as private get;
		has as private has;
		getAll as private getAll;
	}

	/** @var string $name */
	private string $name;
	/** @var string $color */
	private string $color;
	/** @var int $maxPlayers */
	private int $maxPlayers = -1;

	/**
	 * @param string $name
	 * @param string $color
	 */
	public function __construct(string $name, string $color)
	{
		$this->name = $name;
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getColor(): string
	{
		return $this->color;
	}

	/**
	 * @param int $value
	 * @return void
	 */
	public function setMaxPlayers(int $value): void
	{
		$this->maxPlayers = $value;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(): int
	{
		return $this->maxPlayers;
	}
}
