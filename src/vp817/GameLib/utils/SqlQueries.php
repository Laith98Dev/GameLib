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

final class SqlQueries
{
	public const INIT = "gl-queries.init";
	public const ADD_ARENA = "gl-queries.add-arena";
	public const UPDATE_ARENA_SPAWNS = "gl-queries.update-arena-spawns";
	public const UPDATE_ARENA_LOBBY_SETTINGS = "gl-queries.update-arena-lobby-settings";
	public const UPDATE_ARENA_DATA = "gl-queries.update-arena-data";
	public const UPDATE_ARENA_EXTRA_DATA = "gl-queries.update-arena-extra-data";
	public const GET_ARENA_DATA = "gl-queries.get-arena-data";
	public const REMOVE_ARENA = "gl-queries.remove-arena";
	public const GET_ALL_ARENAS = "gl-queries.get-all-arenas";
}
