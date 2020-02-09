{strip}
<h4>Grouping Information</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>Grouped Work ID</th>
		<td>{$recordDriver->getPermanentId()}</td>
	</tr>
	{foreach from=$groupedWorkDetails key='field' item='value'}
	<tr>
		<th>{$field|escape}</th>
		<td>
			{$value|escape}
		</td>
	</tr>
	{/foreach}
</table>

{if (!empty($alternateTitles))}
	<h4>Alternate Titles and Authors</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>Title</th><th>Author</th></tr>
		</thead>
		{foreach from=$alternateTitles item="alternateTitle"}
			<tr><td>{$alternateTitle->alternateTitle}</td><td>{$alternateTitle->alternateAuthor}</td></tr>
		{/foreach}
	</table>
{/if}
{/strip}