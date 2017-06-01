<?php
namespace Rsc\BetterBlockHints\Model\TemplateEngine\Decorator;

use Magento\Framework\View\TemplateEngineInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Developer\Model\TemplateEngine\Decorator\DebugHints as BaseDebugHints;

class DebugHints extends BaseDebugHints
{
    protected $stylesScriptsSent = false;
    protected $_subject;
    protected $_i = 0;

    /**
     * @param \Magento\Framework\View\TemplateEngineInterface $subject
     * @param bool $showBlockHints Whether to include block into the debugging information or not
     */
    public function __construct(TemplateEngineInterface $subject, $showBlockHints)
    {
        $this->_subject = $subject;
        $this->_showBlockHints = $showBlockHints;
    }

    public function render(BlockInterface $block, $templateFile, array $dictionary = [])
    {
        $parentBlocks = $this->getParentBlocks($block);
        $blockHtml = $this->_subject->render($block, $templateFile, $dictionary);
        $blockName = $block->getNameInLayout();
        $blockClass = '<small class="debugging-hints__extends">' .get_parent_class($block) . '</small>';
        $blockClass .= '<span class="debugging-hints__class">' . get_class($block) . '</span>';

        $content = '';
        if(!$this->stylesScriptsSent) {
            $this->stylesScriptsSent = true;
            $content .= $this->stylesScripts();
        }

        $debugBlockId = 'debug_block_' . $this->_i++;

        $content .=  '<div class="debugging-hints" data-id="' . $debugBlockId . '">'
                   .      $blockHtml
                   . '</div>';

        $content .=   '<div class="debugging-hints__block-info" id="' . $debugBlockId . '" style="z-index:'.($this->_i+999).'">'
                    . (!empty($blockName) ? '     <strong>Block Name:</strong> ' . $blockName . '<br><br>' : '')
                    . '     <strong>Block Template:</strong> ' . $templateFile . '<br><br>'
                    . '     <strong>Block Class:</strong> ' . $blockClass . '<br><br>'
                    . (count($parentBlocks) ? '     <strong>Block Parents:</strong>' . implode(' &raquo; ', $parentBlocks) . '<br><br>' : '' )
                    . '</div>';

        return $content;
    }

    protected function getParentBlocks($block)
    {
        $parents = [];
        while($parent = $block->getParentBlock())
        {
            $parents[] = $parent->getNameInLayout();
            $block = $parent;
        }
        return array_reverse($parents);
    }

    protected function stylesScripts() {
        return <<<HTML
            <style type="text/css">
                .debugging-hints {
                    position: relative;
                }
                .pathHints .debugging-hints {
                    margin: 6px;
                    padding: 6px;
                    transition: all .25s ease;
                    will-change: margin, padding;
                    cursor: pointer;
                }
                .pathHints .debugging-hints:after {
                    position: absolute;
                    display: block;
                    content: " ";
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    border: 1px dotted red !important;
                    pointer-events: none;
                }
                .pathHints .debugging-hints:hover {
                    background: rgba(255, 0, 0, .15);
                }
                .pathHints .debugging-hints:hover:after {
                    border-style: solid !important;
                }
                .debugging-hints__block-info {
                    display: none;
                    width: 250px;
                    font-size: 12px;
                    font-family: monospace;
                    background: white !important;
                    color: black !important;
                    padding: 12px;
                    word-break: break-all;
                    box-shadow: 0 0 0 25px rgba(0, 0, 0, .25);
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                }
                .debugging-hints__block-info strong {
                    display: block;
                    text-transform: uppercase;
                    font-size: 8px;
                    font-family: sans-serif;
                    background: black;
                    color: white !important;
                    padding: .25rem;
                    margin-bottom: 12px;
                }
                .debugging-hints__extends {
                    display: block;
                    font-size: 9px;
                    color: #888;
                    position: relative;
                }
                .debugging-hints__extends:after {
                    display: block;
                    content: " ";
                    border-left: 1px solid black;
                    border-bottom: 1px solid black;
                    height: 10px;
                    width: 3px;
                    position: absolute;
                    bottom: -10px;
                }
                .debugging-hints__class {
                    padding-left: 7px;
                }
            </style>

            <script type="text/javascript">

                // Shift, alt, command, control
                var keyToggles = [91, 93, 17, 16, 18]
                addEvent(document, "keydown", function (e) {
                    e = e || window.event
                    if(keyToggles.indexOf(e.keyCode) > -1) {
                        pathHintsOn()
                    }
                })

                addEvent(document, "keyup", function (e) {
                    e = e || window.event
                    if(keyToggles.indexOf(e.keyCode) > -1) {
                        pathHintsOff()
                    }
                })

                // Add events to our debug blocks
                ready(function() {
                    var hintBlocks = document.querySelectorAll('.debugging-hints')
                    hintBlocks.forEach(function(hintBlock) {
                        var hintBlockInfoId = hintBlock.dataset.id
                        hintBlock.onclick = function(e) {
                            if(e.eventPhase == Event.AT_TARGET) {
                                showDebugBlock(hintBlockInfoId)
                                e.stopPropagation()
                            } else {
                                showDebugBlock(hintBlockInfoId)
                            }
                        }
                    })
                })

                function showDebugBlock(id) {
                    console.log(this)
                    var hintBlocks = document.querySelectorAll('.debugging-hints__block-info')
                    hintBlocks.forEach(function(hintBlock) {
                        if(hintBlock.id == id) {
                            hintBlock.style.display = 'block'
                        } else {
                            hintBlock.style.display = 'none'
                        }
                    })
                }

                function hideDebugBlocks() {
                    var hintBlocks = document.querySelectorAll('.debugging-hints__block-info')
                    hintBlocks.forEach(function(hintBlock) {
                        hintBlock.style.display = 'none'
                    })
                }

                function pathHintsOn() {
                    hideDebugBlocks()
                    document.body.classList.add("pathHints")
                }

                function pathHintsOff() {
                    document.body.classList.remove("pathHints")
                }

                function ready(fn) {
                    if (document.readyState != 'loading') {
                        fn()
                    } else {
                        document.addEventListener('DOMContentLoaded', fn)
                    }
                }
                function addEvent(element, eventName, callback) {
                    if (element.addEventListener) {
                        element.addEventListener(eventName, callback, false)
                    } else if (element.attachEvent) {
                        element.attachEvent("on" + eventName, callback)
                    } else {
                        element["on" + eventName] = callback
                    }
                }
            </script>
HTML;
    }
}
