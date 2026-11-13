<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Testing\TestCase;
use CnA\Workflow\Step;
use CnA\Workflow\Workflow;
use CnA\Workflow\WorkflowEngine;

class WorkflowTest extends TestCase
{
    public function testWorkflowSuccessfulExecution(): void
    {
        $engine = new WorkflowEngine();

        $workflow = new Workflow('order_fulfillment');
        $workflow->addStep(Step::make('validate_order', function (array $context) {
            $context['order_valid'] = true;
            return $context;
        }));
        $workflow->addStep(Step::make('charge_payment', function (array $context) {
            $context['charged'] = true;
            $context['transaction_id'] = 'tx_12345';
            return $context;
        }));
        $workflow->addStep(Step::make('send_email', function (array $context) {
            $context['email_sent'] = true;
            return $context;
        }));

        $engine->register($workflow);

        $this->assertTrue($engine->has('order_fulfillment'));
        $this->assertEquals(1, count($engine->listWorkflows()));

        $executionId = $engine->start('order_fulfillment', ['customer_id' => 42]);
        $this->assertNotNull($executionId);

        $execution = $engine->getExecution($executionId);
        $this->assertNotNull($execution);
        $this->assertEquals('completed', $execution['status']);
        $this->assertTrue($execution['context']['order_valid']);
        $this->assertTrue($execution['context']['charged']);
        $this->assertTrue($execution['context']['email_sent']);
        $this->assertEquals('tx_12345', $execution['context']['transaction_id']);
        $this->assertEquals(3, count($execution['history']));
    }

    public function testWorkflowSagaCompensationOnFailure(): void
    {
        $engine = new WorkflowEngine();
        $compensated = false;

        $workflow = new Workflow('hotel_booking');
        $workflow->addStep(
            Step::make('reserve_room', function (array $context) {
                $context['room_reserved'] = true;
                return $context;
            })->setCompensate(function (array $context) use (&$compensated) {
                $compensated = true;
            })
        );

        $workflow->addStep(
            Step::make('charge_card', function (array $context) {
                throw new \RuntimeException('Payment gateway timeout');
            })
        );

        $engine->register($workflow);

        $failed = false;
        try {
            $engine->start('hotel_booking', ['booking_id' => 99]);
        } catch (\RuntimeException $e) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Workflow should throw exception on failure');
        $this->assertTrue($compensated, 'Saga compensation callback must be executed on step failure');
    }
}
