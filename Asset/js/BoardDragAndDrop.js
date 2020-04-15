Kanboard.BoardDragAndDrop = function(app) {
    this.app = app;
    this.savingInProgress = false;
};

Kanboard.BoardDragAndDrop.prototype.execute = function() {
    if (this.app.hasId("board")) {
        this.executeListeners();
        this.dragAndDrop();
    }
};

Kanboard.BoardDragAndDrop.prototype.dragAndDrop = function() {
    var self = this;
    var dropzone = $(".board-task-list");

    // Run for every Board List, connecting the Items within the same project id
    dropzone.each(function() {
        // Set dropzone height to the height of the table cell
        $(this).css("min-height", $(this).parent().height());

        var params = {
            forcePlaceholderSize: true,
            tolerance: "pointer",
            connectWith: ".sortable-column",
            placeholder: "draggable-placeholder",
            items: ".draggable-item",
            stop: function(event, ui) {
                var task = ui.item;
                var taskId = task.attr('data-task-id');
                var taskPosition = task.attr('data-position');
                var taskColumnId = task.attr('data-column-id');
                var taskCategoryId = task.attr('data-category-id');
                var taskOwnerId = task.attr('data-owner-id');
                var taskProjectId = task.attr('data-project-id');
				var taskSwimlaneId = task.attr('data-swimlane-id');

                var newColumnId = task.parent().attr("data-column-id");
                var newSwimlaneId = task.parent().attr('data-swimlane-id');
                var newProjectId = task.parent().attr('data-project-id');
                var newPosition = task.index() + 1;

                var boardId = task.closest("table").attr("data-project-id");
                var saveURL = task.closest("table").attr("data-save-url");

                task.removeClass("draggable-item-selected");

                if (newColumnId != taskColumnId || newSwimlaneId != taskSwimlaneId || newPosition != taskPosition || newProjectId != taskProjectId ) {
                    self.changeTaskState(taskId);
                    self.save(saveURL, taskId, taskCategoryId, taskOwnerId, taskColumnId, taskProjectId, newColumnId, newPosition, newSwimlaneId, newProjectId );                        
                }
            },
            start: function(event, ui) {
                ui.item.addClass("draggable-item-selected");
                ui.placeholder.height(ui.item.height());
            }
        };

        if (isMobile.any) {
            $(".task-board-sort-handle").css("display", "inline");
            params.handle = ".task-board-sort-handle";
        }
        
        $(this).sortable(params);
    });
};

Kanboard.BoardDragAndDrop.prototype.changeTaskState = function(taskId) {
    var task = $("div[data-task-id=" + taskId + "]");
    task.addClass('task-board-saving-state');
    task.find('.task-board-saving-icon').show();
};

Kanboard.BoardDragAndDrop.prototype.save = function(saveURL, taskId, taskCategoryId, taskOwnerId, srcColumnId, srcProjectId, dstColumnId, dstPosition, dstSwimlaneId, dstProjectId ) {
    var self = this;
    self.app.showLoadingIcon();
    self.savingInProgress = true;

    $.ajax({
        cache: false,
        url: saveURL,
        contentType: "application/json",
        type: "POST",
        processData: false,
        data: JSON.stringify({
            "task_id": taskId,
            "src_column_id": srcColumnId,
            "dst_column_id": dstColumnId,
            "dst_swimlane_id": dstSwimlaneId,
            "dst_project_id": dstProjectId,
            "src_project_id": srcProjectId,
            "category_id": taskCategoryId,
            "owner_id": taskOwnerId,
            "position": dstPosition
        }),
        success: function(data) {
            self.setContent(dstProjectId,data);
			if( srcProjectId != dstProjectId ) {
				self.refresh(srcProjectId);
			} else {
				self.savingInProgress = false;				
			}
        },
        error: function() {
            self.app.hideLoadingIcon();
            self.savingInProgress = false;
        },
        statusCode: {
            403: function(data) {
                window.alert(data.responseJSON.message);
                document.location.reload(true);
            }
        }
    });
};

Kanboard.BoardDragAndDrop.prototype.refresh = function(boardId) {
	var self = this;
	self.savingInProgress = true;
    var reloadDataUrl = $("table[id=board][data-project-id=" + boardId + "]").attr("data-check-url");
	$.ajax({
		cache: false,
		url: reloadDataUrl,
		statusCode: {
			200: function(data) {                        
				self.app.hideLoadingIcon();
				self.app.get("BoardDragAndDrop").setContent(boardId, data);
				self.savingInProgress = false;
			},
			304: function () {
				self.app.hideLoadingIcon();
				self.savingInProgress = false;
			}
		}
	});
};

Kanboard.BoardDragAndDrop.prototype.setContent = function(boardId, data) {

    $("div[id=board-container][data-project-id=" + boardId + "]").replaceWith(data);

    this.app.hideLoadingIcon();
    this.executeListeners();
    this.dragAndDrop();
};

Kanboard.BoardDragAndDrop.prototype.executeListeners = function() {
    for (var className in this.app.controllers) {
        var controller = this.app.get(className);

        if (typeof controller.onBoardRendered === "function") {
            controller.onBoardRendered();
        }
    }
};
