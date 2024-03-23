<?php

namespace Illuminate\Tests\Integration\Events;

use Exception;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\InvokeQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class QueuedClosureListenerTest extends TestCase
{
    public function testAnonymousQueuedListenerIsQueued()
    {
        Bus::fake();

        Event::listen(\Illuminate\Events\queueable(function (TestEvent $event) {
            //
        })->catch(function (TestEvent $event) {
            //
        })->onConnection(null)->onQueue(null));

        Event::dispatch(new TestEvent);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class == InvokeQueuedClosure::class;
        });
    }

    public function testAnonymousQueuedListenerIsExecuted()
    {
        cache()->set('wasCalled', false);

        try {
            DB::transaction(function () {
                Event::listen(\Illuminate\Events\queueable(function (TestEvent $event) {
                    cache()->set('wasCalled', true);
                    //
                })->catch(function (TestEvent $event) {
                    //
                })->onConnection(null)->onQueue(null));

                Event::dispatch(new TestEvent());

                throw new Exception('foo');
            });
        } catch (Exception $e) {
        }

        self::assertTrue(cache()->get('wasCalled'));
    }

    public function testAnonymousQueuedListenerIsNotExecutedIfTransactionFails()
    {
        cache()->set('wasCalled', false);

        try {
            DB::transaction(function () {
                Event::listen(\Illuminate\Events\queueable(function (TestEvent $event){
                    cache()->set('wasCalled', true);
                    //
                })->catch(function (TestEvent $event) {
                    //
                })->onConnection(null)->onQueue(null)->afterCommit());

                Event::dispatch(new TestEvent());

                throw new Exception('foo');
            });
        } catch (Exception $e) {
        }

        self::assertFalse(cache()->get('wasCalled'));
    }
    public function testAnonymousQueuedListenerIsExecutedAfterCommit()
    {
        cache()->set('wasCalled', false);

        DB::transaction(function () {
            Event::listen(\Illuminate\Events\queueable(function (TestEvent $event) {
                cache()->set('wasCalled', true);
            })->catch(function (TestEvent $event) {
                //
            })->onConnection(null)->onQueue(null)->afterCommit());

            Event::dispatch(new TestEvent());
        });

        self::assertTrue(cache()->get('wasCalled'));
    }
}

class TestEvent
{
    //
}
