@extends('header')

@section('head')
    @parent

    <style type="text/css">
        .kanban {
            overflow-x: auto;
            white-space: nowrap;
        }

        .kanban-column {
            padding: 10px;
            height: 100%;
            width: 230px;
            margin-right: 12px;
            display: inline-block;
            vertical-align: top;
            white-space: normal;
            cursor: pointer;
        }

        .kanban-column-header {
            font-weight: bold;
            padding-bottom: 12px;
        }

        .kanban-column-header .pull-left {
            width: 90%;
        }

        .kanban-column-header .fa-times {
            color: #888888;
            padding-bottom: 6px;
        }

        .kanban-column-header input {
            width: 190px;
        }

        .kanban-column-header .view {
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .kanban-column-row {
            margin-bottom: -8px;
        }

        .kanban-column-row .fa-circle {
            float:right;
            padding-top: 10px;
            padding-right: 8px;
        }

        .kanban-column-row .view div {
            padding: 8px;
        }

        .kanban-column textarea {
            resize: vertical;
            width: 100%;
            padding-left: 8px;
            padding-top: 8px;
        }

        .kanban-column .edit {
            display: none;
        }

        .kanban-column .editing .edit {
            display: block;
        }

        .kanban-column .editing .view {
            display: none;
        }​

        .project-group0 { color: #000000; }
        .project-group1 { color: #1c9f77; }
        .project-group2 { color: #d95d02; }
        .project-group3 { color: #716cb1; }
        .project-group4 { color: #e62a8b; }
        .project-group5 { color: #5fa213; }
        .project-group6 { color: #e6aa04; }
        .project-group7 { color: #a87821; }
        .project-group8 { color: #676767; }


    </style>

@stop

@section('content')

    <script type="text/javascript">

        var statuses = {!! $statuses !!};
        var tasks = {!! $tasks !!};
        var projects = {!! $projects !!};
        var clients = {!! $clients !!};

        var projectMap = {};
        var clientMap = {};

        ko.bindingHandlers.enterkey = {
            init: function (element, valueAccessor, allBindings, viewModel) {
                var callback = valueAccessor();
                $(element).keypress(function (event) {
                    var keyCode = (event.which ? event.which : event.keyCode);
                    if (keyCode === 13) {
                        callback.call(viewModel);
                        return false;
                    }
                    return true;
                });
            }
        };

        ko.bindingHandlers.selected = {
            update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
                var selected = ko.utils.unwrapObservable(valueAccessor());
                if (selected) element.select();
            }
        };

        function ViewModel() {
            var self = this;

            self.statuses = ko.observableArray();
            for (var i=0; i<statuses.length; i++) {
                var status = statuses[i];
                var statusModel = new StatusModel(status);
                self.statuses.push(statusModel);
            }
            self.statuses.push(new StatusModel());

            for (var i=0; i<projects.length; i++) {
                var project = projects[i];
                projectMap[project.public_id] = new ProjectModel(project);
            }

            for (var i=0; i<clients.length; i++) {
                var client = clients[i];
                //clientMap[client.public_id] = client;
            }

            for (var i=0; i<tasks.length; i++) {
                var task = tasks[i];
                var taskModel = new TaskModel(task);
                var statusModel = self.statuses()[tasks.task_status_id || 0];
                statusModel.tasks.push(taskModel);
            }

            self.onDragged = function() {

            }
        }

        function StatusModel(data) {
            var self = this;
            self.name = ko.observable();
            self.is_blank = ko.observable(false);
            self.is_editing_status = ko.observable(false);
            self.is_header_hovered = ko.observable(false);
            self.tasks = ko.observableArray();
            self.new_task = new TaskModel();

            self.onHeaderMouseOver = function() {
                self.is_header_hovered(true);
            }

            self.onHeaderMouseOut = function() {
                self.is_header_hovered(false);
            }

            self.inputValue = ko.computed({
                read: function () {
                    return self.is_blank() ? '' : self.name();
                },
                write: function(value) {
                    self.name(value);
                    if (self.is_blank()) {
                        self.is_blank(false);
                        model.statuses.push(new StatusModel());
                    }
                }
            });

            self.placeholder = ko.computed(function() {
                return self.is_blank() ? '{{ trans('texts.add_status') }}...' : '';
            })

            self.startEdit = function() {
                self.is_editing_status(true);
            }

            self.endEdit = function() {
                self.is_editing_status(false);
            }

            self.onDragged = function() {

            }

            self.archiveStatus = function() {
                window.model.statuses.remove(self);
            }

            self.cancelNewTask = function() {
                if (self.new_task.is_blank()) {
                    self.new_task.description('');
                }
                self.new_task.endEdit();
            }

            self.saveNewTask = function() {
                var task = new TaskModel({
                    description: self.new_task.description()
                })
                self.tasks.push(task);
                self.new_task.reset();
                self.is_blank(false);
                self.endEdit();
            }

            if (data) {
                ko.mapping.fromJS(data, {}, this);
                self.tasks.push(new TaskModel({description:'testing'}));
            } else {
                self.name('{{ trans('texts.add_status') }}...');
                self.is_blank(true);
            }
        }

        function TaskModel(data) {
            var self = this;
            self.description = ko.observable('');
            self.description.orig = ko.observable('');
            self.is_blank = ko.observable(false);
            self.is_editing_task = ko.observable(false);
            self.project = ko.observable();

            self.projectColor = ko.computed(function() {
                if (! self.project()) {
                    return '';
                }
                var projectId = self.project().public_id();
                var colorNum = (projectId-1) % 8;
                console.log('project-group' + (colorNum+1));
                return 'project-group' + (colorNum+1);
            })

            self.startEdit = function() {
                self.description.orig(self.description());
                self.is_editing_task(true);
                $('.kanban-column-row.editing textarea').focus();
            }

            self.endEdit = function() {
                self.is_editing_task(false);
            }

            self.onDragged = function() {

            }

            self.cancelEditTask = function() {
                if (self.is_blank()) {
                    self.description('');
                } else {
                    self.description(self.description.orig());
                }

                self.endEdit();
            }

            self.saveEditTask = function(task) {
                self.endEdit();
            }

            self.reset = function() {
                self.endEdit();
                self.description('');
                self.is_blank(true);
            }

            self.mapping = {
                'project': {
                    create: function(options) {
                        return projectMap[options.data.public_id];
                    }
                }
            }

            if (data) {
                ko.mapping.fromJS(data, self.mapping, this);
            } else {
                //self.description('{{ trans('texts.add_task') }}...');
                self.is_blank(true);
            }
        }

        function ProjectModel(data) {
            var self = this;
            self.name = ko.observable();

            if (data) {
                ko.mapping.fromJS(data, {}, this);
            }
        }

        $(function() {
            window.model = new ViewModel();
            ko.applyBindings(model);
        });

    </script>

    <!-- <div data-bind="text: ko.toJSON(model)"></div> -->
    <script id="itemTmpl" type="text/html">

    </script>


    <div class="kanban">
        <div data-bind="sortable: { data: statuses, as: 'status', afterMove: onDragged, allowDrop: true, connectClass: 'connect-column' }">
            <div class="well kanban-column">

                <div class="kanban-column-header" data-bind="css: { editing: is_editing_status }, event: { mouseover: onHeaderMouseOver, mouseout: onHeaderMouseOut }">
                    <div class="pull-left" data-bind="event: { click: startEdit }">
                        <div class="view" data-bind="text: name"></div>
                        <input class="edit" type="text" data-bind="value: inputValue, hasfocus: is_editing_status, selected: is_editing_status,
                                placeholder: placeholder, event: { blur: endEdit }, enterkey: endEdit"/>
                    </div>
                    <div class="pull-right" data-bind="click: archiveStatus, visible: ! is_blank() &amp;&amp; is_header_hovered">
                        <i class="fa fa-times" title="{{ trans('texts.archive') }}"></i>
                    </div><br/>
                </div>

                <div data-bind="sortable: { data: tasks, as: 'task', afterMove: onDragged, allowDrop: true, connectClass: 'connect-row' }">
                    <div class="kanban-column-row" data-bind="css: { editing: is_editing_task }">
                        <div data-bind="event: { click: startEdit }">
                            <div class="view panel" data-bind="visible: ! is_blank()">
                                <i class="fa fa-circle" data-bind="visible: project, css: projectColor"></i>
                                <div data-bind="text: description"></div>
                            </div>
                        </div>
                        <div class="edit">
                            <textarea data-bind="value: description, valueUpdate: 'afterkeydown', enterkey: saveEditTask"></textarea>
                            <div class="pull-right">
                                <button type='button' class='btn btn-default btn-sm' data-bind="click: cancelEditTask">
                                    {{ trans('texts.cancel') }}
                                </button>
                                <button type='button' class='btn btn-success btn-sm' data-bind="click: saveEditTask">
                                    {{ trans('texts.save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kanban-column-footer" data-bind="css: { editing: new_task.is_editing_task }, with: new_task">
                    <div data-bind="event: { click: startEdit }">
                        <div class="view panel" data-bind="visible: ! is_blank()">
                            <div data-bind="text: description"></div>
                        </div>
                        <a href="#" class="view text-muted" style="font-size:13px" data-bind="visible: is_blank">
                            {{ trans('texts.new_task') }}...
                        </a>
                    </div>
                    <div class="edit">
                        <textarea data-bind="value: description, valueUpdate: 'afterkeydown', enterkey: $parent.saveNewTask"></textarea>
                        <div class="pull-right">
                            <button type='button' class='btn btn-default btn-sm' data-bind="click: $parent.cancelNewTask">
                                {{ trans('texts.cancel') }}
                            </button>
                            <button type='button' class='btn btn-success btn-sm' data-bind="click: $parent.saveNewTask">
                                {{ trans('texts.save') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@stop