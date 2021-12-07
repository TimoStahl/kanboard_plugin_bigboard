<?php
// check if plugin "starred projects" is available

use Kanboard\Core\Ldap\User;

try {
	if ($this->app->starredProjectsModel->find($project['id'], $this->user->getId())) {
		$fav = "<i class='fa fa-star' title='" . t('Favorite project') . "'></i>";
	} else $fav = "";
} catch (Exception $e) {
	$fav = "";
}

if ($project['is_private']) {
	$priv = "<i class='fa fa-lock fa-fw' title='" . t('Private project') . "'></i>";
} else $priv = "";

if ($this->app->bigboardModel->collapseFind($project['id'], $this->user->getId())) {
	$open = "none";
	$close = "inline-block";
} else {
	$open = "inline-block";
	$close = "none";
}
?>

<section id="main">
	<div class='header btnbox'>
		<?php
		if ($this->user->hasProjectAccess('ProjectViewController', 'show', $project['id'])) {
			echo $this->url->icon('cog', '', 'ProjectViewController', 'show', array('project_id' => $project['id']), false, 'btn');
		}
		?>
		<div class='btn bbprojectcollapse'>
			<span class='collapsible' data-project-id="<?= $this->text->e($project['id']) ?>" id="head-<?= $this->text->e($project['id']) ?>">
				<i id="open-<?= $this->text->e($project['id']) ?>" style="display:<?= $open ?>" class="fa fa-folder-open open"></i>
				<i id="close-<?= $this->text->e($project['id']) ?>" style="display:<?= $close ?>" class="fa fa-folder close"></i>
				<i id="wip-<?= $this->text->e($project['id']) ?>" style="display:none" class="fa fa-cog fa-spin fa-fw"></i>
			</span>
			<small>#<?= $this->text->e($project['id']) ?></small>
			<?= $fav ?>
			<?= $this->text->e($project['name']) ?>
			<?= $priv ?>
			<?php if (!empty($project['description'])) : ?>
				<?= $this->app->tooltipMarkdown($project['description']) ?>
			<?php endif ?>
		</div>
	</div>

	<?php
	if ($this->app->bigboardModel->collapseFind($project['id'], $this->user->getId())) {
		$display = "none";
	} else {
		$display = "block";
	}
	?>
	<div class="BBcontent" data-project-id='<?= $project['id'] ?>' style="display:<?= $display ?>">
		<?= $this->render('bigboard:board/table_container', array(
			'project' => $project,
			'swimlanes' => $swimlanes,
			'board_private_refresh_interval' => $board_private_refresh_interval,
			'board_highlight_period' => $board_highlight_period,
		)) ?>
	</div>
</section>