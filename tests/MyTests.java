package tests;

import static org.junit.Assert.assertEquals;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import src.exo1.Email;
import src.exo1.Subject;


public class MyTests {

    Subject su;
    MockObserver o1;
    Email email;

    @BeforeEach
    void setUp() {
        su = new Subject();
        o1 = new MockObserver("yann");
        email = new Email("email");
    }
    

    @Test
    public void testNotifSEnded(){
        su.registerObserver(o1);
        su.notifyObservers();
        assertEquals(o1.getMsgReceived(), "hola");

    }


    @Test
    public void testNotifStrategy(){
        su.NotifStrategy(email);
        assertEquals(email.name, "email");
        
    }

}
