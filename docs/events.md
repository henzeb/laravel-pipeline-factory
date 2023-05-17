# The Events Pipe

Laravel Pipelines do not dispatch events. This pipes ensures that every pipe
dispatches them.

This pipe is preferably encapsulating all pipes you are going to process

````php
use Henzeb\Pipeline\Facades\Pipe;
use Illuminate\Pipeline\Pipeline;

Pipeline::through(
     Pipe::events(
        [
            YourFirstPipe::class
            YourSecondPipe::class
        ]
     )
);
````

To identify the pipeline inside the event listeners, you can give the pipeline
an id:

````php
use Henzeb\Pipeline\Facades\Pipe;
use Illuminate\Pipeline\Pipeline;

Pipeline::through(
     Pipe::events(
        [
            YourFirstPipe::class
            YourSecondPipe::class
        ],
        'ThisIsMyFirstPipe'
     )
);
````

## Events

### PipelineProcessing

The `PipelineProcessing` is dispatched when the events pipe is being executed.

- pipelineId: The pipeline id, `ThisIsMyFirstPipe`
- pipeCount: The count of pipes and sub pipes (excluding any pipes that has them).
- passable: The value that is passed through the pipeline.

### PipelineProcessed

The `PipelineProcessed` is dispatched when the events pipe is finished.

- pipelineId: The pipeline id, `ThisIsMyFirstPipe`
- pipeCount: The count of pipes and child pipes (excluding the pipes that has them)
- passable: The value that is passed through the pipeline

### PipeProcessing

The `PipeProcessing` is dispatched when a pipe or child pipe is being executed.

- pipelineId: The pipeline id, `ThisIsMyFirstPipe`
- pipeId: Each pipe is given an integer id in order of appearance
- pipe: The FQCN of the pipe under execution
- passable: The value that is passed through the pipeline

### PipeProcessed

The `PipeProcessed` is dispatched when a pipe or child pipe is finished.

- pipelineId: The pipeline id, `ThisIsMyFirstPipe`
- pipeId: Each pipe is given an integer id in order of appearance
- pipe: The FQCN of the pipe under execution
- passable: The value that is passed through the pipeline

## The Event Pipe

You can also handle events for a single pipe if you want to.

````php
use Henzeb\Pipeline\Facades\Pipe;
use Illuminate\Pipeline\Pipeline;

Pipeline::through(
     Pipe::event(
        YourFirstPipe::class
     ),
     Pipe::event(
        YourFirstPipe::class,
        'ThePipelineId',
        1 // the pipeId
     ),
);
````

Note: when using `event`, only the `PipeProcessing` and the `PipeProcessed` are
dispatched.
