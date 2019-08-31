/*  Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

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

jQuery(document).ready(checkData);

function checkPwd()
	{
	$ = jQuery;
	if ($("#newpassword").val() != $("#newpassword-repeat").val())
		{
		$("#err-pwd").css("display", "");
		return(false);
		}
	else
		{
		$("#err-pwd").css("display", "none");
		}
	};
	
function checkData()
	{
	jQuery(function($)
		{
		$('#newpassword-repeat').blur(checkPwd);
		$('#choose-pwd').submit(checkPwd);
		});
	}
