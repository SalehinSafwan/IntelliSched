<?php

namespace App\Services\Chatbot;

final class TokenType
{
    // Question words
    public const WHEN = 'WHEN';    
    public const WHO = 'WHO';            
    public const WHERE = 'WHERE';        

    // Keywords
    public const CLASS_KW = 'CLASS';
    public const COURSE_KW = 'COURSE_KW'; 
    public const TEACHER_KW = 'TEACHER_KW'; 
    public const ROOM_KW = 'ROOM_KW';    
    public const TEACHES = 'TEACHES';    
    public const BY = 'BY';               
    public const NEXT = 'NEXT';           
    public const NOW = 'NOW';             
    public const FIRST = 'FIRST';        
    public const LAST = 'LAST';         
    public const FREE = 'FREE';          
    public const COUNT = 'COUNT';         
    public const WEEK = 'WEEK';           
    public const HELP = 'HELP';          

    // Entities
    public const DATE = 'DATE';          
    public const DAY = 'DAY';            
    public const COURSE = 'COURSE'; 
    public const TEACHER = 'TEACHER';

    public const UNKNOWN = 'UNKNOWN';

    public const ENTITIES = [self::DATE, self::DAY, self::COURSE, self::TEACHER];
}
