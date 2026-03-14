<?php

namespace Tests\Unit\Controller;

use Josix\Controller\NoteController;
use Josix\Core\Http\Response;
use Josix\Model\Note;
use Josix\Repository\NoteRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Unit\Controller\AbstractControllerTestCase;

/**
 * @extends AbstractControllerTestCase<NoteController>
 */
class NoteControllerTest extends AbstractControllerTestCase
{
    private NoteRepository&MockObject $noteRepository;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepository::class);
        parent::setUp();
    }

    protected function createController(): object
    {
        return new NoteController($this->noteRepository);
    }

    public function testGetNotePageWithAllNotes(): void
    {
        $notes = [
            $this->createStub(Note::class),
            $this->createStub(Note::class),
        ];

        $this->noteRepository
            ->expects($this->once())
            ->method('findAllOrderedByNewest')
            ->willReturn($notes);

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with('notes.html.twig', ['title' => 'Notes — Josix SQLite Demo', 'notes' => $notes])
            ->willReturn('Rendered Template');

        /** @var Response $result */
        $result = $this->controller->index();

        $this->assertSame('Rendered Template', $result->getContent());
    }
}
