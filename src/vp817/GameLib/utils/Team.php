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

namespace vp817\GameLib\utils;

use pocketmine\block\utils\DyeColor;
use pocketmine\utils\EnumTrait;
use pocketmine\utils\TextFormat;
use vp817\GameLib\traits\ArenaPlayerTrait;

/**
 * @method static Team BLACK()
 * @method static Team BLUE()
 * @method static Team GREEN()
 * @method static Team LIME()
 * @method static Team CYAN()
 * @method static Team PURPLE()
 * @method static Team ORANGE()
 * @method static Team LIGHT_GRAY()
 * @method static Team GRAY()
 * @method static Team LIGHT_BLUE()
 * @method static Team RED()
 * @method static Team MAGENTA()
 * @method static Team PINK()
 * @method static Team YELLOW()
 * @method static Team WHITE()
 */
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
	use EnumTrait {
		__construct as Enum___construct;
	}

	/**
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public static function setup(): void
	{
		self::registerAll(
			new self(
				name: "black",
				color: TextFormat::BLACK,
				dyeColor: DyeColor::BLACK()
			),
			new self(
				name: "blue",
				color: TextFormat::DARK_BLUE,
				dyeColor: DyeColor::BLUE()
			),
			new self(
				name: "green",
				color: TextFormat::DARK_GREEN,
				dyeColor: DyeColor::GREEN()
			),
			new self(
				name: "lime",
				color: TextFormat::GREEN,
				dyeColor: DyeColor::LIME()
			),
			new self(
				name: "cyan",
				color: TextFormat::DARK_AQUA,
				dyeColor: DyeColor::CYAN()
			),
			new self(
				name: "purple",
				color: TextFormat::DARK_PURPLE,
				dyeColor: DyeColor::PURPLE()
			),
			new self(
				name: "orange",
				color: TextFormat::GOLD,
				dyeColor: DyeColor::ORANGE()
			),
			new self(
				name: "light_gray",
				color: TextFormat::GRAY,
				dyeColor: DyeColor::LIGHT_GRAY()
			),
			new self(
				name: "gray",
				color: TextFormat::DARK_GRAY,
				dyeColor: DyeColor::GRAY()
			),
			new self(
				name: "light_blue",
				color: TextFormat::AQUA,
				dyeColor: DyeColor::LIGHT_BLUE()
			),
			new self(
				name: "red",
				color: TextFormat::DARK_RED,
				dyeColor: DyeColor::RED()
			),
			new self(
				name: "magenta",
				color: TextFormat::LIGHT_PURPLE,
				dyeColor: DyeColor::MAGENTA()
			),
			new self(
				name: "pink",
				color: TextFormat::LIGHT_PURPLE,
				dyeColor: DyeColor::PINK()
			),
			new self(
				name: "yellow",
				color: TextFormat::YELLOW,
				dyeColor: DyeColor::YELLOW()
			),
			new self(
				name: "white",
				color: TextFormat::WHITE,
				dyeColor: DyeColor::WHITE()
			),
		);
	}

	/**
	 * @param Team $team
	 */
	public static function registerCustomTeam(
		string $name,
		string $color,
		DyeColor $dyeColor
	): void {
		self::register(member: new self(
			name: $name,
			color: $color,
			dyeColor: $dyeColor
		));
	}

	/**
	 * @param string $value
	 * @return Team|null
	 */
	public static function fromString(string $value): ?Team
	{
		return self::_registryFromString(name: $value) ?? null;
	}

	/**
	 * @param string $name
	 * @param string $color
	 * @param DyeColor $dyeColor
	 */
	private function __construct(
		private string $name,
		private string $color,
		private DyeColor $dyeColor
	) {
		$this->Enum___construct($name);
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
	 * @return DyeColor
	 */
	public function getDyeColor(): DyeColor
	{
		return $this->dyeColor;
	}
}
