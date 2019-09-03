/*	Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

jQuery(document).ready(function($)
	{
	$('div.multiple_check :checkbox').on('change', function(evt) 
		{
		parentdiv = $(this).parents("div.multiple_check")
		var max_votes = parentdiv.attr("value");
		if (parentdiv.find(":checkbox:checked").length > max_votes)
			{
			alert("Hai solo "+max_votes+" voti a disposizione per questa domanda");
			$(this).attr('checked', false);
			}
		})
	})