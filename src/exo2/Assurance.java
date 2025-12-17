package src.exo2;

public class Assurance extends Decorator {

    public Assurance(Commande commande) {
        super(commande);
    }

    @Override
    public int calculatePrice() {
        return super.calculatePrice() + 15;
    }

    @Override
    void pay() {
    System.out.println("le client a payee : "+calculatePrice());

        
    }
    
}
